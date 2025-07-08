<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed: " . $database->getError());
    }
    
    $user = new User($db);

    if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
        header("Location: ../index.php");
        exit();
    }

    $teacher_id = $_SESSION['user_id'];
    $teacher_info = $user->getTeacherInfo($teacher_id);
    
    if (!$teacher_info) {
        throw new Exception("Информация о преподавателе не найдена");
    }

    $success_message = '';
    $error_message = '';

    // Обработка формы обновления данных
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $username = trim($_POST['username'] ?? '');
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            
            // Валидация
            if (empty($username) || empty($first_name) || empty($last_name)) {
                $error_message = "Все поля обязательны для заполнения";
            } elseif (strlen($username) < 3) {
                $error_message = "Имя пользователя должно содержать минимум 3 символа";
            } elseif (strlen($first_name) < 2 || strlen($last_name) < 2) {
                $error_message = "Имя и фамилия должны содержать минимум 2 символа";
            } else {
                // Обновляем профиль
                $result = $user->updateTeacher($teacher_id, $username, null, $first_name, $last_name);
                
                if ($result) {
                    $success_message = "Профиль успешно обновлен";
                    $teacher_info = $user->getTeacherInfo($teacher_id); // Обновляем информацию
                } else {
                    $error_message = "Ошибка при обновлении профиля. Возможно, такое имя пользователя уже существует.";
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Валидация
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "Все поля пароля обязательны для заполнения";
            } elseif (strlen($new_password) < 6) {
                $error_message = "Новый пароль должен содержать минимум 6 символов";
            } elseif ($new_password !== $confirm_password) {
                $error_message = "Пароли не совпадают";
            } else {
                // Проверяем текущий пароль
                // Получаем хэш пароля из базы данных
                $query = "SELECT password FROM users WHERE id = :teacher_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':teacher_id', $teacher_id);
                $stmt->execute();
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data && password_verify($current_password, $user_data['password'])) {
                    // Обновляем пароль
                    $result = $user->updateTeacher($teacher_id, null, $new_password, null, null);
                    
                    if ($result) {
                        $success_message = "Пароль успешно изменен";
                    } else {
                        $error_message = "Ошибка при изменении пароля";
                    }
                } else {
                    $error_message = "Неверный текущий пароль";
                }
            }
        }
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$page_title = "Аккаунт";
$page_icon = "fas fa-user-circle";
require_once 'header.php';
?>

<style>
    .form-section {
        margin-bottom: 2rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e1e5e9;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: background 0.3s;
    }
    
    .btn-primary:hover {
        background: #5a6fd8;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: background 0.3s;
        margin-left: 0.5rem;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
    
    .account-info {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 2rem;
    }
    
    .account-info h3 {
        margin-bottom: 1rem;
        color: #495057;
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #495057;
    }
    
    .info-value {
        color: #6c757d;
    }
    
    .password-section {
        border-top: 2px solid #e9ecef;
        padding-top: 2rem;
        margin-top: 2rem;
    }
    
    .form-row {
        display: flex;
        gap: 1rem;
    }
    
    .form-row .form-group {
        flex: 1;
    }
    
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
        }
    }
</style>

<div class="container">
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-user-circle"></i> Информация об аккаунте</h2>
        
        <div class="account-info">
            <h3>Текущие данные</h3>
            <div class="info-item">
                <span class="info-label">Имя пользователя:</span>
                <span class="info-value"><?php echo htmlspecialchars($teacher_info['username']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Имя:</span>
                <span class="info-value"><?php echo htmlspecialchars($teacher_info['first_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Фамилия:</span>
                <span class="info-value"><?php echo htmlspecialchars($teacher_info['last_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Дата регистрации:</span>
                <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($teacher_info['created_at'])); ?></span>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fas fa-edit"></i> Редактировать профиль</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="username">Имя пользователя (никнейм):</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($teacher_info['username']); ?>" 
                           required minlength="3" maxlength="50">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Имя:</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($teacher_info['first_name']); ?>" 
                               required minlength="2" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Фамилия:</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($teacher_info['last_name']); ?>" 
                               required minlength="2" maxlength="100">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
            </form>
        </div>

        <div class="password-section">
            <h3><i class="fas fa-key"></i> Изменить пароль</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Текущий пароль:</label>
                    <input type="password" id="current_password" name="current_password" 
                           required minlength="6">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">Новый пароль:</label>
                        <input type="password" id="new_password" name="new_password" 
                               required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль:</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               required minlength="6">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-key"></i> Изменить пароль
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Проверка совпадения паролей
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        
        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Пароли не совпадают');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Проверка совпадения паролей при вводе нового пароля
    document.getElementById('new_password').addEventListener('input', function() {
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword.value && this.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Пароли не совпадают');
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
</script>

</body>
</html>
