<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../includes/translations.php';

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
                $error_message = $translations['ru']['all_fields_required'];
            } elseif (strlen($username) < 3) {
                $error_message = $translations['ru']['username_min_length'];
            } elseif (strlen($first_name) < 2 || strlen($last_name) < 2) {
                $error_message = $translations['ru']['name_min_length'];
            } else {
                // Обновляем профиль
                $result = $user->updateTeacher($teacher_id, $username, null, $first_name, $last_name);
                
                if ($result) {
                    $success_message = $translations['ru']['profile_updated_success'];
                    $teacher_info = $user->getTeacherInfo($teacher_id); // Обновляем информацию
                } else {
                    $error_message = $translations['ru']['profile_update_error'];
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Валидация
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = $translations['ru']['all_password_fields_required'];
            } elseif (strlen($new_password) < 6) {
                $error_message = $translations['ru']['new_password_min_length'];
            } elseif ($new_password !== $confirm_password) {
                $error_message = $translations['ru']['passwords_not_match'];
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
                        $success_message = $translations['ru']['password_changed_success'];
                    } else {
                        $error_message = $translations['ru']['password_change_error'];
                    }
                } else {
                    $error_message = $translations['ru']['current_password_wrong'];
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
    <?php include 'language_switcher.php'; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success" data-translate-key="success_message">
            <i class="fas fa-check-circle"></i> <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error" data-translate-key="error_message">
            <i class="fas fa-exclamation-triangle"></i> <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2 data-translate-key="account_info"><i class="fas fa-user-circle"></i> Информация об аккаунте</h2>
        
        <div class="account-info">
            <h3 data-translate-key="current_data">Текущие данные</h3>
            <div class="info-item">
                <span class="info-label" data-translate-key="username_current">Имя пользователя:</span>
                <span class="info-value"><?php echo htmlspecialchars($teacher_info['username']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label" data-translate-key="name_current">Имя:</span>
                <span class="info-value"><?php echo htmlspecialchars($teacher_info['first_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label" data-translate-key="surname_current">Фамилия:</span>
                <span class="info-value"><?php echo htmlspecialchars($teacher_info['last_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label" data-translate-key="registration_date">Дата регистрации:</span>
                <span class="info-value"><?php echo date('d.m.Y H:i', strtotime($teacher_info['created_at'])); ?></span>
            </div>
        </div>

        <div class="form-section">
            <h3 data-translate-key="edit_profile"><i class="fas fa-edit"></i> Редактировать профиль</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="username" data-translate-key="username_field">Имя пользователя (никнейм):</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($teacher_info['username']); ?>" 
                           required minlength="3" maxlength="50">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" data-translate-key="first_name_field">Имя:</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($teacher_info['first_name']); ?>" 
                               required minlength="2" maxlength="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" data-translate-key="last_name_field">Фамилия:</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($teacher_info['last_name']); ?>" 
                               required minlength="2" maxlength="100">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" data-translate-key="save_changes">
                    <i class="fas fa-save"></i> Сохранить изменения
                </button>
            </form>
        </div>

        <div class="password-section">
            <h3 data-translate-key="change_password"><i class="fas fa-key"></i> Изменить пароль</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password" data-translate-key="current_password">Текущий пароль:</label>
                    <input type="password" id="current_password" name="current_password" 
                           required minlength="6">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password" data-translate-key="new_password">Новый пароль:</label>
                        <input type="password" id="new_password" name="new_password" 
                               required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" data-translate-key="confirm_new_password">Подтвердите пароль:</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               required minlength="6">
                    </div>
                </div>
                
                <button type="submit" class="btn-primary" data-translate-key="change_password_button">
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
            // Получаем перевод из JavaScript
            const errorMessage = translations[currentLang] && translations[currentLang]['passwords_not_match'] 
                ? translations[currentLang]['passwords_not_match'] 
                : 'Пароли не совпадают';
            this.setCustomValidity(errorMessage);
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Проверка совпадения паролей при вводе нового пароля
    document.getElementById('new_password').addEventListener('input', function() {
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword.value && this.value !== confirmPassword.value) {
            const errorMessage = translations[currentLang] && translations[currentLang]['passwords_not_match'] 
                ? translations[currentLang]['passwords_not_match'] 
                : 'Пароли не совпадают';
            confirmPassword.setCustomValidity(errorMessage);
        } else {
            confirmPassword.setCustomValidity('');
        }
    });
    
    // Функция для перевода сообщений
    function translateMessages() {
        // Переводим сообщения об успехе
        const successMessage = document.querySelector('.alert-success span');
        if (successMessage) {
            const text = successMessage.textContent.trim();
            let translationKey = '';
            
            if (text.includes('Профиль успешно обновлен') || text.includes('Profile updated successfully') || text.includes('Профиль сәтті жаңартылды')) {
                translationKey = 'profile_updated_success';
            } else if (text.includes('Пароль успешно изменен') || text.includes('Password changed successfully') || text.includes('Құпия сөз сәтті өзгертілді')) {
                translationKey = 'password_changed_success';
            }
            
            if (translationKey && translations[currentLang] && translations[currentLang][translationKey]) {
                successMessage.textContent = translations[currentLang][translationKey];
            }
        }
        
        // Переводим сообщения об ошибке
        const errorMessage = document.querySelector('.alert-error span');
        if (errorMessage) {
            const text = errorMessage.textContent.trim();
            let translationKey = '';
            
            if (text.includes('Все поля обязательны для заполнения') || text.includes('All fields are required') || text.includes('Барлық өрістерді толтыру міндетті')) {
                translationKey = 'all_fields_required';
            } else if (text.includes('Имя пользователя должно содержать минимум 3 символа') || text.includes('Username must contain at least 3 characters') || text.includes('Пайдаланушы аты кемінде 3 таңбадан тұруы керек')) {
                translationKey = 'username_min_length';
            } else if (text.includes('Имя и фамилия должны содержать минимум 2 символа') || text.includes('Name and surname must contain at least 2 characters') || text.includes('Аты мен тегі кемінде 2 таңбадан тұруы керек')) {
                translationKey = 'name_min_length';
            } else if (text.includes('Ошибка при обновлении профиля') || text.includes('Error updating profile') || text.includes('Профильді жаңарту кезінде қате')) {
                translationKey = 'profile_update_error';
            } else if (text.includes('Все поля пароля обязательны для заполнения') || text.includes('All password fields are required') || text.includes('Құпия сөздің барлық өрістерін толтыру міндетті')) {
                translationKey = 'all_password_fields_required';
            } else if (text.includes('Новый пароль должен содержать минимум 6 символов') || text.includes('New password must contain at least 6 characters') || text.includes('Жаңа құпия сөз кемінде 6 таңбадан тұруы керек')) {
                translationKey = 'new_password_min_length';
            } else if (text.includes('Пароли не совпадают') || text.includes('Passwords do not match') || text.includes('Құпия сөздер сәйкес келмейді')) {
                translationKey = 'passwords_not_match';
            } else if (text.includes('Неверный текущий пароль') || text.includes('Current password is incorrect') || text.includes('Ағымдағы құпия сөз дұрыс емес')) {
                translationKey = 'current_password_wrong';
            } else if (text.includes('Ошибка при изменении пароля') || text.includes('Error changing password') || text.includes('Құпия сөзді өзгерту кезінде қате')) {
                translationKey = 'password_change_error';
            }
            
            if (translationKey && translations[currentLang] && translations[currentLang][translationKey]) {
                errorMessage.textContent = translations[currentLang][translationKey];
            }
        }
    }
    
    // Добавляем перевод сообщений к основной функции перевода
    const originalTranslatePage = translatePage;
    translatePage = function() {
        originalTranslatePage();
        translateMessages();
    };
    
    // Переводим сообщения при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        translateMessages();
    });
</script>

</body>
</html>
