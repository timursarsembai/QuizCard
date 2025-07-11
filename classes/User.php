<?php
class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, password, role, first_name, last_name, teacher_id, email_verified, email 
                  FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['teacher_id'] = $row['teacher_id'];
                $_SESSION['email_verified'] = $row['email_verified'];
                $_SESSION['email'] = $row['email'];
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function createTeacher($username, $password, $first_name, $last_name, $email = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, role, first_name, last_name, email, email_verified) 
                  VALUES (:username, :password, 'teacher', :first_name, :last_name, :email, 0)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        
        try {
            if ($stmt->execute()) {
                $user_id = $this->conn->lastInsertId();
                
                // Отправляем письмо подтверждения, если указан email
                if (!empty($email)) {
                    $this->sendVerificationEmailByUserId($user_id);
                }
                
                return $user_id;
            }
            return false;
        } catch (PDOException $e) {
            // Обрабатываем ошибку дублирования имени пользователя
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function createStudent($username, $password, $first_name, $last_name, $teacher_id) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, role, first_name, last_name, teacher_id) 
                  VALUES (:username, :password, 'student', :first_name, :last_name, :teacher_id)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function getStudentsByTeacher($teacher_id) {
        $query = "SELECT id, username, first_name, last_name, created_at 
                  FROM users WHERE teacher_id = :teacher_id AND role = 'student'
                  ORDER BY last_name, first_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentsCountByTeacher($teacher_id) {
        $query = "SELECT COUNT(*) FROM users WHERE teacher_id = :teacher_id AND role = 'student'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function getStudentInfo($student_id, $teacher_id) {
        $query = "SELECT id, username, first_name, last_name, created_at 
                  FROM users 
                  WHERE id = :student_id AND teacher_id = :teacher_id AND role = 'student'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteStudent($student_id, $teacher_id) {
        $query = "DELETE FROM users WHERE id = :student_id AND teacher_id = :teacher_id AND role = 'student'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function updateStudent($student_id, $teacher_id, $username = null, $password = null, $first_name = null, $last_name = null) {
        // Проверяем, что ученик принадлежит этому преподавателю
        $check_query = "SELECT id FROM users WHERE id = :student_id AND teacher_id = :teacher_id AND role = 'student'";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':student_id', $student_id);
        $check_stmt->bindParam(':teacher_id', $teacher_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            return false; // Ученик не найден или не принадлежит преподавателю
        }
        
        // Формируем динамический запрос обновления
        $updates = [];
        $params = [':student_id' => $student_id];
        
        if ($username !== null) {
            $updates[] = "username = :username";
            $params[':username'] = $username;
        }
        
        if ($password !== null) {
            $updates[] = "password = :password";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($first_name !== null) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $first_name;
        }
        
        if ($last_name !== null) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $last_name;
        }
        
        if (empty($updates)) {
            return true; // Нечего обновлять
        }
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :student_id";
        $stmt = $this->conn->prepare($query);
        
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            // Обрабатываем ошибку дублирования имени пользователя
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
    
    public function getTeacherInfo($teacher_id) {
        $query = "SELECT id, username, first_name, last_name, email, created_at 
                  FROM users 
                  WHERE id = :teacher_id AND role = 'teacher'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateTeacher($teacher_id, $username = null, $password = null, $first_name = null, $last_name = null) {
        // Проверяем, что пользователь существует и является преподавателем
        $check_query = "SELECT id FROM users WHERE id = :teacher_id AND role = 'teacher'";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':teacher_id', $teacher_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            return false; // Преподаватель не найден
        }
        
        // Формируем динамический запрос обновления
        $updates = [];
        $params = [':teacher_id' => $teacher_id];
        
        if ($username !== null) {
            $updates[] = "username = :username";
            $params[':username'] = $username;
        }
        
        if ($password !== null) {
            $updates[] = "password = :password";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($first_name !== null) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $first_name;
        }
        
        if ($last_name !== null) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $last_name;
        }
        
        if (empty($updates)) {
            return true; // Нечего обновлять
        }
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        
        try {
            $result = $stmt->execute($params);
            
            // Обновляем сессию, если данные изменились
            if ($result) {
                if ($username !== null) {
                    $_SESSION['username'] = $username;
                }
                if ($first_name !== null) {
                    $_SESSION['first_name'] = $first_name;
                }
                if ($last_name !== null) {
                    $_SESSION['last_name'] = $last_name;
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            // Обрабатываем ошибку дублирования имени пользователя
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
    
    /**
     * Методы для работы с подтверждением email
     */
    
    /**
     * Генерация криптографически стойкого токена
     */
    public function generateVerificationToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Проверка существования имени пользователя
     */
    public function isUsernameExists($username) {
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Проверка существования email
     */
    public function isEmailExists($email) {
        if (empty($email)) return false;
        
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Регистрация преподавателя (обертка для createTeacher)
     */
    public function register($username, $password, $first_name, $last_name, $email = null) {
        return $this->createTeacher($username, $password, $first_name, $last_name, $email);
    }
    
    /**
     * Отправка письма подтверждения по ID пользователя
     */
    public function sendVerificationEmailByUserId($user_id) {
        require_once __DIR__ . '/../config/email_config.php';
        require_once __DIR__ . '/../includes/translations.php';
        
        // Получаем информацию о пользователе
        $query = "SELECT email, first_name, last_name FROM users WHERE id = :user_id AND role = 'teacher'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || empty($user['email'])) {
            return false;
        }
        
        return $this->sendVerificationEmail($user['email'], $user_id, $user['first_name'], $user['last_name']);
    }
    
    /**
     * Отправка письма подтверждения
     */
    public function sendVerificationEmail($email, $user_id = null, $first_name = '', $last_name = '') {
        require_once __DIR__ . '/../config/email_config.php';
        require_once __DIR__ . '/../includes/translations.php';
        
        if (empty($email)) {
            return false;
        }
        
        // Если не передан user_id, ищем по email
        if ($user_id === null) {
            $query = "SELECT id, first_name, last_name FROM users WHERE email = :email AND role = 'teacher'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                return false;
            }
            
            $user_id = $user['id'];
            $first_name = $user['first_name'];
            $last_name = $user['last_name'];
        }
        
        // Проверяем лимиты отправки
        $rate_check = EmailConfig::canSendEmail($user_id, $this->conn);
        if (!$rate_check['allowed']) {
            return ['success' => false, 'reason' => $rate_check['reason'], 'data' => $rate_check];
        }
        
        try {
            // Генерируем токен
            $token = $this->generateVerificationToken();
            $expires = date('Y-m-d H:i:s', time() + (EmailConfig::$token_expiry_hours * 3600));
            
            // Сохраняем токен в БД
            $query = "UPDATE users SET 
                     verification_token = :token, 
                     verification_token_expires = :expires,
                     last_verification_sent = NOW()
                     WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expires', $expires);
            $stmt->bindParam(':user_id', $user_id);
            
            if (!$stmt->execute()) {
                return ['success' => false, 'reason' => 'db_error'];
            }
            
            // Получаем текущий язык
            $language = $_SESSION['language'] ?? 'ru';
            
            // Формируем письмо
            $verification_url = EmailConfig::getVerificationUrl($token);
            $subject = EmailConfig::getSubject($language);
            $headers = EmailConfig::getHeaders($language);
            
            $body = $this->getEmailTemplate($verification_url, $first_name, $last_name, $language);
            
            // Отправляем письмо
            $mail_sent = mail($email, $subject, $body, $headers);
            
            // Логируем результат
            $log_status = $mail_sent ? 'sent' : 'failed';
            EmailConfig::logEmailSent($user_id, $email, $token, $this->conn, $log_status);
            
            if ($mail_sent) {
                return ['success' => true, 'expires_hours' => EmailConfig::$token_expiry_hours];
            } else {
                return ['success' => false, 'reason' => 'mail_failed'];
            }
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'reason' => 'system_error'];
        }
    }
    
    /**
     * Подтверждение email по токену
     */
    public function verifyEmail($token) {
        require_once __DIR__ . '/../config/email_config.php';
        
        if (empty($token)) {
            return ['success' => false, 'reason' => 'invalid_token'];
        }
        
        try {
            // Ищем пользователя с данным токеном
            $query = "SELECT id, email, verification_token_expires, email_verified 
                     FROM users 
                     WHERE verification_token = :token 
                     AND role = 'teacher'";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'reason' => 'token_not_found'];
            }
            
            if ($user['email_verified']) {
                return ['success' => false, 'reason' => 'already_verified'];
            }
            
            // Проверяем срок действия токена
            if ($user['verification_token_expires'] && strtotime($user['verification_token_expires']) < time()) {
                return ['success' => false, 'reason' => 'token_expired'];
            }
            
            // Подтверждаем email
            $query = "UPDATE users SET 
                     email_verified = 1,
                     verification_token = NULL,
                     verification_token_expires = NULL
                     WHERE id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user['id']);
            
            if ($stmt->execute()) {
                // Обновляем лог
                $log_query = "UPDATE email_verification_logs 
                             SET status = 'verified', verified_at = NOW() 
                             WHERE token = :token";
                $log_stmt = $this->conn->prepare($log_query);
                $log_stmt->bindParam(':token', $token);
                $log_stmt->execute();
                
                // Обновляем сессию если пользователь авторизован
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
                    $_SESSION['email_verified'] = 1;
                }
                
                return ['success' => true, 'user_id' => $user['id']];
            } else {
                return ['success' => false, 'reason' => 'db_error'];
            }
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return ['success' => false, 'reason' => 'system_error'];
        }
    }
    
    /**
     * Проверка статуса подтверждения email
     */
    public function isEmailVerified($user_id = null) {
        if ($user_id === null) {
            return isset($_SESSION['email_verified']) && $_SESSION['email_verified'];
        }
        
        $query = "SELECT email_verified FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['email_verified'];
    }
    
    /**
     * Повторная отправка письма подтверждения
     */
    public function resendVerificationEmail($user_id) {
        $query = "SELECT email, first_name, last_name, email_verified 
                 FROM users 
                 WHERE id = :user_id AND role = 'teacher'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'reason' => 'user_not_found'];
        }
        
        if ($user['email_verified']) {
            return ['success' => false, 'reason' => 'already_verified'];
        }
        
        if (empty($user['email'])) {
            return ['success' => false, 'reason' => 'no_email'];
        }
        
        return $this->sendVerificationEmail($user['email'], $user_id, $user['first_name'], $user['last_name']);
    }
    
    /**
     * Получение HTML шаблона письма
     */
    private function getEmailTemplate($verification_url, $first_name, $last_name, $language = 'ru') {
        require_once __DIR__ . '/../includes/translations.php';
        
        $translations = [
            'ru' => [
                'title' => 'Подтвердите ваш email адрес',
                'greeting' => 'Здравствуйте',
                'message' => 'Спасибо за регистрацию в QuizCard! Для завершения регистрации подтвердите ваш email адрес, нажав на кнопку ниже:',
                'button_text' => 'Подтвердить email',
                'alternative' => 'Если кнопка не работает, скопируйте и вставьте эту ссылку в браузер:',
                'expires' => 'Ссылка действительна в течение 24 часов.',
                'ignore' => 'Если вы не регистрировались в QuizCard, просто проигнорируйте это письмо.',
                'footer' => 'С уважением,<br>Команда QuizCard'
            ],
            'en' => [
                'title' => 'Verify your email address',
                'greeting' => 'Hello',
                'message' => 'Thank you for registering with QuizCard! To complete your registration, please verify your email address by clicking the button below:',
                'button_text' => 'Verify Email',
                'alternative' => 'If the button doesn\'t work, copy and paste this link into your browser:',
                'expires' => 'This link is valid for 24 hours.',
                'ignore' => 'If you didn\'t register with QuizCard, please ignore this email.',
                'footer' => 'Best regards,<br>QuizCard Team'
            ],
            'kk' => [
                'title' => 'Email мекенжайыңызды растаңыз',
                'greeting' => 'Сәлеметсіз бе',
                'message' => 'QuizCard жүйесінде тіркелгеніңіз үшін рахмет! Тіркелуді аяқтау үшін төмендегі батырманы басып email мекенжайыңызды растаңыз:',
                'button_text' => 'Email растау',
                'alternative' => 'Егер батырма жұмыс істемесе, бұл сілтемені көшіріп браузерге қойыңыз:',
                'expires' => 'Сілтеме 24 сағат бойы жарамды.',
                'ignore' => 'Егер сіз QuizCard жүйесінде тіркелмеген болсаңыз, бұл хатты елемеңіз.',
                'footer' => 'Құрметпен,<br>QuizCard командасы'
            ]
        ];
        
        $t = $translations[$language] ?? $translations['ru'];
        $full_name = trim("$first_name $last_name");
        
        return "
        <!DOCTYPE html>
        <html lang='$language'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$t['title']}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f7f7f7; min-height: 100vh;'>
                <tr>
                    <td align='center' style='padding: 40px 20px;'>
                        <table width='600' cellpadding='0' cellspacing='0' style='background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 600px; width: 100%;'>
                            <!-- Header -->
                            <tr>
                                <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; border-radius: 10px 10px 0 0;'>
                                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>📚 QuizCard</h1>
                                    <p style='color: #ffffff; margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;'>{$t['title']}</p>
                                </td>
                            </tr>
                            
                            <!-- Body -->
                            <tr>
                                <td style='padding: 40px 30px;'>
                                    <h2 style='color: #333; margin: 0 0 20px 0; font-size: 24px;'>{$t['greeting']}" . ($full_name ? ", $full_name" : "") . "!</h2>
                                    
                                    <p style='color: #666; margin: 0 0 30px 0; font-size: 16px;'>{$t['message']}</p>
                                    
                                    <!-- Button -->
                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                        <tr>
                                            <td align='center' style='padding: 20px 0;'>
                                                <a href='$verification_url' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 50px; font-size: 18px; font-weight: bold; text-align: center; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);'>{$t['button_text']}</a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <p style='color: #666; margin: 30px 0 20px 0; font-size: 14px;'>{$t['alternative']}</p>
                                    <p style='background: #f8f9fa; padding: 15px; border-radius: 5px; word-break: break-all; font-size: 14px; color: #666; margin: 0 0 20px 0;'>$verification_url</p>
                                    
                                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                                        <p style='color: #856404; margin: 0; font-size: 14px;'>⏰ {$t['expires']}</p>
                                    </div>
                                    
                                    <p style='color: #999; font-size: 14px; margin: 30px 0 0 0;'>{$t['ignore']}</p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='background: #f8f9fa; padding: 30px; text-align: center; border-radius: 0 0 10px 10px; border-top: 1px solid #eee;'>
                                    <p style='color: #666; margin: 0; font-size: 16px;'>{$t['footer']}</p>
                                    <p style='color: #999; margin: 10px 0 0 0; font-size: 12px;'>© " . date('Y') . " QuizCard. Все права защищены.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}
?>
