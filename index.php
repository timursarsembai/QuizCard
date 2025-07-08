<?php
session_start();

$error = '';
$success = '';
$activeTab = 'login';

if ($_POST) {
    try {
        require_once 'config/database.php';
        require_once 'classes/User.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$database->isConnected()) {
            $error = 'Ошибка подключения к базе данных. <a href="setup.php">Проверить настройку БД</a>';
        } else {
            $user = new User($db);

            // Обработка входа
            if (isset($_POST['login'])) {
                $username = trim($_POST['username']);
                $password = $_POST['password'];

                if (empty($username) || empty($password)) {
                    $error = 'Введите имя пользователя и пароль';
                } elseif ($user->login($username, $password)) {
                    if ($user->getRole() === 'teacher') {
                        header("Location: teacher/dashboard.php");
                        exit();
                    } else {
                        $error = 'Эта страница предназначена только для преподавателей';
                    }
                } else {
                    $error = 'Неверные данные для входа';
                }
            }
            
            // Обработка регистрации
            if (isset($_POST['register'])) {
                $activeTab = 'register';
                
                $username = trim($_POST['reg_username']);
                $password = $_POST['reg_password'];
                $confirm_password = $_POST['reg_confirm_password'];
                $first_name = trim($_POST['reg_first_name']);
                $last_name = trim($_POST['reg_last_name']);
                $email = trim($_POST['reg_email']);

                // Валидация
                if (strlen($password) < 6) {
                    $error = 'Пароль должен содержать минимум 6 символов';
                } elseif ($password !== $confirm_password) {
                    $error = 'Пароли не совпадают';
                } elseif (empty($username) || empty($first_name) || empty($last_name)) {
                    $error = 'Все поля обязательны для заполнения';
                } else {
                    // Создание преподавателя
                    if ($user->createTeacher($username, $password, $first_name, $last_name, $email)) {
                        $success = 'Регистрация успешна! Теперь вы можете войти в систему.';
                        $activeTab = 'login';
                    } else {
                        $error = 'Ошибка регистрации. Возможно, пользователь с таким именем уже существует.';
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Ошибка системы. Попробуйте позже.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Панель преподавателя</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .logo {
            text-align: center;
            padding: 2rem 2rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .logo h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .logo p {
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .tabs {
            display: flex;
            background: #f8f9fa;
        }

        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            background: none;
            font-size: 1rem;
            color: #666;
        }

        .tab.active {
            background: white;
            color: #667eea;
            font-weight: 600;
        }

        .tab:hover:not(.active) {
            background: #e9ecef;
        }

        .tab-content {
            padding: 2rem;
        }

        .tab-pane {
            display: none;
        }

        .tab-pane.active {
            display: block;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        input[type="text"], input[type="password"], input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .error {
            background: #ffe6e6;
            color: #d00;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #d00;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }

        .student-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .student-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .student-link a:hover {
            color: #5a6fd8;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .demo-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #666;
        }

        .demo-info h4 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .logo h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo">
            <h1>📚 QuizCard</h1>
            <p>Панель управления для преподавателей</p>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('login')">Вход</button>
            <button class="tab" onclick="switchTab('register')">Регистрация</button>
        </div>

        <div class="tab-content">
            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Вкладка входа -->
            <div id="login-tab" class="tab-pane <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Имя пользователя:</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Пароль:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" name="login" class="btn">Войти</button>
                </form>
            </div>

            <!-- Вкладка регистрации -->
            <div id="register-tab" class="tab-pane <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="reg_first_name">Имя:</label>
                            <input type="text" id="reg_first_name" name="reg_first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_last_name">Фамилия:</label>
                            <input type="text" id="reg_last_name" name="reg_last_name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_username">Имя пользователя:</label>
                        <input type="text" id="reg_username" name="reg_username" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_email">Email (опционально):</label>
                        <input type="email" id="reg_email" name="reg_email">
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Пароль:</label>
                        <input type="password" id="reg_password" name="reg_password" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_confirm_password">Подтвердите пароль:</label>
                        <input type="password" id="reg_confirm_password" name="reg_confirm_password" required>
                    </div>

                    <button type="submit" name="register" class="btn">Зарегистрироваться</button>
                </form>
            </div>

            <div class="student-link">
                <a href="student_login.php">👨‍🎓 Вход для учеников</a>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Удаляем активный класс со всех вкладок и панелей
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            
            // Добавляем активный класс к выбранной вкладке
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Автоматическое скрытие сообщений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.error, .success');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
