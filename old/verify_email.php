<?php
/**
 * Страница подтверждения email адреса
 */

// Принудительно устанавливаем HTML заголовки
header('Content-Type: text/html; charset=UTF-8');

session_start();

// Обрабатываем смену языка из URL
if (isset($_GET['lang']) && in_array($_GET['lang'], ['kk', 'ru', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
}

require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'config/email_config.php';

// Получаем токен из URL
$token = $_GET['token'] ?? '';
$message = '';
$message_type = 'error';
$show_login_link = false;
$show_resend_link = false;
$user_data = null;

// Очищаем истекшие токены
$database = new Database();
$db = $database->getConnection();

if ($database->isConnected()) {
    EmailConfig::cleanupExpiredTokens($db);
    
    $user = new User($db);
    
    if (!empty($token)) {
        $result = $user->verifyEmail($token);
        
        switch ($result['reason'] ?? '') {
            case null: // success
                if ($result['success']) {
                    $message = 'email_verified_success';
                    $message_type = 'success';
                    $show_login_link = true;
                    
                    // Получаем информацию о пользователе
                    $query = "SELECT first_name, last_name, email FROM users WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $result['user_id']);
                    $stmt->execute();
                    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'invalid_token':
                $message = 'error_invalid_token';
                break;
                
            case 'token_not_found':
                $message = 'error_token_not_found';
                break;
                
            case 'already_verified':
                $message = 'error_already_verified';
                $message_type = 'warning';
                $show_login_link = true;
                break;
                
            case 'token_expired':
                $message = 'error_token_expired';
                $show_resend_link = true;
                break;
                
            default:
                $message = 'error_verification_failed';
        }
    } else {
        $message = 'error_no_token';
    }
} else {
    $message = 'error_db_connection';
}

// Обработка повторной отправки
if ($_POST && isset($_POST['resend_email'])) {
    $email = trim($_POST['email']);
    
    if (!empty($email)) {
        // Находим пользователя по email
        $query = "SELECT id FROM users WHERE email = :email AND role = 'teacher' AND email_verified = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_record) {
            $resend_result = $user->resendVerificationEmail($user_record['id']);
            
            if ($resend_result['success']) {
                $message = 'email_resent_success';
                $message_type = 'success';
                $show_resend_link = false;
            } else {
                switch ($resend_result['reason']) {
                    case 'rate_limit':
                        $message = 'error_rate_limit';
                        $wait_minutes = $resend_result['data']['wait_minutes'] ?? 5;
                        break;
                    case 'daily_limit':
                        $message = 'error_daily_limit';
                        break;
                    default:
                        $message = 'error_resend_failed';
                }
            }
        } else {
            $message = 'error_email_not_found';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'ru'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('email_verification_title'); ?> - QuizCard</title>
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

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 2rem;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .user-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
        }

        .user-info h3 {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .user-info p {
            color: #6c757d;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
            width: 100%;
            margin: 0.5rem 0;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-link {
            background: transparent;
            color: #667eea;
            text-decoration: underline;
            border: none;
            padding: 0.5rem;
        }

        .btn-link:hover {
            color: #5a67d8;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .language-switcher {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 100;
        }

        .language-switcher select {
            padding: 0.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.9rem;
        }

        .language-switcher select option {
            background: #333;
            color: white;
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        @media (max-width: 480px) {
            .container {
                margin: 1rem;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Переключатель языков -->
    <div class="language-switcher">
        <select onchange="switchLanguage(this.value)">
            <option value="kk" <?php echo ($_SESSION['language'] ?? 'kk') === 'kk' ? 'selected' : ''; ?>>ҚАЗ</option>
            <option value="ru" <?php echo ($_SESSION['language'] ?? 'kk') === 'ru' ? 'selected' : ''; ?>>РУС</option>
            <option value="en" <?php echo ($_SESSION['language'] ?? 'kk') === 'en' ? 'selected' : ''; ?>>ENG</option>
        </select>
    </div>

    <div class="container">
        <div class="header">
            <h1>
                <?php if ($message_type === 'success'): ?>
                    ✅
                <?php elseif ($message_type === 'warning'): ?>
                    ⚠️
                <?php else: ?>
                    ❌
                <?php endif; ?>
                <?php echo translate('email_verification_title'); ?>
            </h1>
            <p><?php echo translate('email_verification_subtitle'); ?></p>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php 
                    echo translate($message);
                    
                    // Добавляем дополнительную информацию для некоторых сообщений
                    if (isset($wait_minutes)) {
                        echo ' ' . sprintf(translate('wait_minutes_info'), $wait_minutes);
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($user_data): ?>
                <div class="user-info">
                    <h3><?php echo translate('welcome_user'); ?></h3>
                    <p>
                        <strong><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></strong><br>
                        <small><?php echo htmlspecialchars($user_data['email']); ?></small>
                    </p>
                </div>
            <?php endif; ?>

            <?php if ($show_resend_link): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email"><?php echo translate('email_label'); ?>:</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="<?php echo translate('enter_email_placeholder'); ?>">
                    </div>
                    <button type="submit" name="resend_email" class="btn btn-primary">
                        <?php echo translate('resend_verification_email'); ?>
                    </button>
                </form>
            <?php endif; ?>

            <div class="actions">
                <?php if ($show_login_link): ?>
                    <a href="login.php" class="btn btn-primary">
                        <?php echo translate('go_to_login'); ?>
                    </a>
                <?php endif; ?>

                <a href="login.php" class="btn btn-secondary">
                    <?php echo translate('back_to_login'); ?>
                </a>

                <?php if (!$show_resend_link && !$show_login_link): ?>
                    <button onclick="window.location.reload()" class="btn btn-link">
                        <?php echo translate('try_again'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Функция переключения языка
        function switchLanguage(lang) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('lang', lang);
            window.location.href = currentUrl.toString();
        }

        // Автообновление страницы при смене языка
        document.addEventListener('DOMContentLoaded', function() {
            const langSelect = document.querySelector('.language-switcher select');
            if (langSelect) {
                langSelect.addEventListener('change', function() {
                    switchLanguage(this.value);
                });
            }
        });
    </script>
</body>
</html>
