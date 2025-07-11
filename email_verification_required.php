<?php
/**
 * Страница уведомления о необходимости подтверждения email
 */

session_start();

// Обрабатываем смену языка из URL
if (isset($_GET['lang']) && in_array($_GET['lang'], ['kk', 'ru', 'en'])) {
    $_SESSION['language'] = $_GET['lang'];
}

require_once 'includes/translations.php';
require_once 'config/database.php';
require_once 'classes/User.php';

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

// Проверяем, не подтвержден ли уже email
if (isset($_SESSION['email_verified']) && $_SESSION['email_verified']) {
    header("Location: teacher/dashboard.php");
    exit();
}

$message = '';
$message_type = 'info';
$user_email = $_SESSION['email'] ?? '';

// Подключение к БД
$database = new Database();
$db = $database->getConnection();

if (!$database->isConnected()) {
    $message = 'error_db_connection';
    $message_type = 'error';
} else {
    $user = new User($db);
    
    // Обработка повторной отправки
    if ($_POST && isset($_POST['resend_verification'])) {
        $result = $user->resendVerificationEmail($_SESSION['user_id']);
        
        if ($result['success']) {
            $message = 'email_resent_success';
            $message_type = 'success';
        } else {
            switch ($result['reason']) {
                case 'rate_limit':
                    $message = 'error_rate_limit';
                    $wait_minutes = $result['data']['wait_minutes'] ?? 5;
                    break;
                case 'daily_limit':
                    $message = 'error_daily_limit';
                    $max_attempts = $result['data']['max_attempts'] ?? 5;
                    break;
                case 'already_verified':
                    // Обновляем сессию и перенаправляем
                    $_SESSION['email_verified'] = 1;
                    header("Location: teacher/dashboard.php");
                    exit();
                case 'no_email':
                    $message = 'error_no_email_address';
                    break;
                default:
                    $message = 'error_resend_failed';
            }
            $message_type = 'error';
        }
    }
    
    // Получаем информацию о пользователе
    $query = "SELECT first_name, last_name, email, last_verification_sent FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_info) {
        $user_email = $user_info['email'];
        $last_sent = $user_info['last_verification_sent'];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'ru'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('email_verification_required'); ?> - QuizCard</title>
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
            max-width: 600px;
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
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
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

        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .user-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .user-info h3 {
            color: #495057;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info .detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .user-info .detail:last-child {
            border-bottom: none;
        }

        .user-info .label {
            font-weight: 500;
            color: #6c757d;
        }

        .user-info .value {
            color: #495057;
            font-weight: 600;
        }

        .instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .instructions h4 {
            color: #856404;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instructions ol {
            margin-left: 1.5rem;
            color: #856404;
        }

        .instructions li {
            margin-bottom: 0.5rem;
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

        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .email-display {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #2196f3;
            margin: 1rem 0;
            text-align: center;
        }

        .email-display .email {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: bold;
            color: #1976d2;
        }

        .last-sent {
            text-align: center;
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 1rem;
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
                 .user-info .detail {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
        }

        .language-switcher {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }

        .language-switcher select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
            font-size: 0.9rem;
        }

        .language-switcher select option {
            padding: 0.5rem;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Переключатель языков -->
        <div class="language-switcher">
            <select onchange="switchLanguage(this.value)">
                <option value="kk" <?php echo ($_SESSION['language'] ?? 'kk') === 'kk' ? 'selected' : ''; ?>>ҚАЗ</option>
                <option value="ru" <?php echo ($_SESSION['language'] ?? 'kk') === 'ru' ? 'selected' : ''; ?>>РУС</option>
                <option value="en" <?php echo ($_SESSION['language'] ?? 'kk') === 'en' ? 'selected' : ''; ?>>ENG</option>
            </select>
        </div>

        <div class="header">
            <div class="icon">📧</div>
            <h1><?php echo translate('email_verification_required'); ?></h1>
            <p><?php echo translate('verification_required_subtitle'); ?></p>
        </div>

        <div class="content">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php 
                    echo translate($message);
                    
                    // Добавляем дополнительную информацию
                    if (isset($wait_minutes)) {
                        echo '<br><small>' . sprintf(translate('wait_minutes_info'), $wait_minutes) . '</small>';
                    }
                    if (isset($max_attempts)) {
                        echo '<br><small>' . sprintf(translate('daily_limit_info'), $max_attempts) . '</small>';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($user_info) && $user_info): ?>
                <div class="user-info">
                    <h3>👤 <?php echo translate('account_information'); ?></h3>
                    <div class="detail">
                        <span class="label"><?php echo translate('name'); ?>:</span>
                        <span class="value"><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></span>
                    </div>
                    <div class="detail">
                        <span class="label"><?php echo translate('username'); ?>:</span>
                        <span class="value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>
                    <div class="detail">
                        <span class="label"><?php echo translate('role'); ?>:</span>
                        <span class="value"><?php echo translate('teacher'); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($user_email)): ?>
                <div class="email-display">
                    <p><?php echo translate('verification_email_sent_to'); ?>:</p>
                    <div class="email"><?php echo htmlspecialchars($user_email); ?></div>
                </div>
            <?php endif; ?>

            <div class="instructions">
                <h4>📋 <?php echo translate('what_to_do_next'); ?></h4>
                <ol>
                    <li><?php echo translate('check_email_inbox'); ?></li>
                    <li><?php echo translate('check_spam_folder'); ?></li>
                    <li><?php echo translate('click_verification_link'); ?></li>
                    <li><?php echo translate('return_to_login'); ?></li>
                </ol>
            </div>

            <div class="actions">
                <?php if (!empty($user_email)): ?>
                    <form method="POST" action="">
                        <button type="submit" name="resend_verification" class="btn btn-primary">
                            🔄 <?php echo translate('resend_verification_email'); ?>
                        </button>
                    </form>
                <?php endif; ?>

                <a href="teacher/account.php" class="btn btn-outline">
                    ⚙️ <?php echo translate('update_email_address'); ?>
                </a>

                <a href="logout.php" class="btn btn-secondary">
                    🚪 <?php echo translate('logout'); ?>
                </a>
            </div>

            <?php if (isset($last_sent) && $last_sent): ?>
                <div class="last-sent">
                    <?php echo translate('last_email_sent'); ?>: 
                    <?php echo date('d.m.Y H:i', strtotime($last_sent)); ?>
                </div>
            <?php endif; ?>
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
                    const currentUrl = new URL(window.location);
                    currentUrl.searchParams.set('lang', this.value);
                    window.location.href = currentUrl.toString();
                });
            }

            // Автоматическая проверка статуса верификации каждые 30 секунд
            setInterval(function() {
                fetch('check_verification_status.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.verified) {
                            window.location.href = 'teacher/dashboard.php';
                        }
                    })
                    .catch(error => {
                        console.log('Verification status check failed:', error);
                    });
            }, 30000);
        });
    </script>
</body>
</html>
