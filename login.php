<?php
session_start();
require_once 'includes/translations.php';

// 1. Централизованные переводы
// $translations = [ ... ]; // Этот массив теперь в includes/translations.php

$error_key = '';
$success_key = '';
$activeTab = 'login';

if ($_POST) {
    try {
        require_once 'config/database.php';
        require_once 'classes/User.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$database->isConnected()) {
            $error_key = 'error_db_connection';
        } else {
            $user = new User($db);

            if (isset($_POST['login'])) {
                $username = trim($_POST['username']);
                $password = $_POST['password'];

                if (empty($username) || empty($password)) {
                    $error_key = 'error_fill_fields';
                } elseif ($user->login($username, $password)) {
                    if ($user->getRole() === 'teacher') {
                        header("Location: teacher/dashboard.php");
                        exit();
                    } else {
                        $error_key = 'error_teacher_only';
                    }
                } else {
                    $error_key = 'error_invalid_credentials';
                }
            }
            
            if (isset($_POST['register'])) {
                $activeTab = 'register';
                
                $username = trim($_POST['reg_username']);
                $password = $_POST['reg_password'];
                $confirm_password = $_POST['reg_confirm_password'];
                $first_name = trim($_POST['reg_first_name']);
                $last_name = trim($_POST['reg_last_name']);
                $email = trim($_POST['reg_email']);

                if (strlen($password) < 6) {
                    $error_key = 'error_password_length';
                } elseif ($password !== $confirm_password) {
                    $error_key = 'error_password_mismatch';
                } elseif (empty($username) || empty($first_name) || empty($last_name)) {
                    $error_key = 'error_all_fields_required';
                } else {
                    if ($user->isUsernameExists($username)) {
                        $error_key = 'error_username_exists';
                    } elseif ($user->isEmailExists($email)) {
                        $error_key = 'error_email_exists';
                    } else {
                        if ($user->register($username, $password, $first_name, $last_name, $email)) {
                            $success_key = 'success_register';
                            $activeTab = 'login';
                        } else {
                            $error_key = 'error_system';
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        $error_key = 'error_system';
    }
}
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="page_title_login">QuizCard - Мұғалімдердің панелі</title>
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
        
        /* Language Switcher */
        .language-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .language-switcher button {
            background: none;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #667eea;
        }
        
        .language-switcher button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .language-switcher button:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
        }
        
        /* Content sections for different languages */
        .lang-content {
            display: none;
        }
        
        .lang-content.active {
            display: block !important;
        }
    </style>
    <script>
        const translations = <?php echo json_encode($translations); ?>;
        const errorKey = '<?php echo $error_key; ?>';
        const successKey = '<?php echo $success_key; ?>';
        let currentLang = 'kk';

        function switchLanguage(lang) {
            if (!lang) {
                lang = 'kk'; // Default language
            }
            currentLang = lang;
            localStorage.setItem('selectedLanguage', lang);

            document.querySelectorAll('[data-translate-key]').forEach(element => {
                const key = element.getAttribute('data-translate-key');
                if (translations[lang] && translations[lang][key]) {
                    // Use innerHTML to support links in translations
                    element.innerHTML = translations[lang][key];
                }
            });

            // Update active button
            document.querySelectorAll('.language-switcher button').forEach(button => {
                if (button.getAttribute('data-lang') === lang) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });

            // Translate error/success messages safely
            const errorDiv = document.querySelector('.error');
            if (errorDiv && errorKey && translations[lang] && translations[lang][errorKey]) {
                errorDiv.innerHTML = translations[lang][errorKey];
            }

            const successDiv = document.querySelector('.success');
            if (successDiv && successKey && translations[lang] && translations[lang][successKey]) {
                successDiv.innerHTML = translations[lang][successKey];
            }
        }

        function getSavedLanguage() {
            return localStorage.getItem('selectedLanguage');
        }

        function switchTab(tabName) {
            document.querySelectorAll('.tab-pane').forEach(pane => {
                if(pane) pane.classList.remove('active');
            });
            const activePane = document.getElementById(tabName);
            if(activePane) activePane.classList.add('active');

            document.querySelectorAll('.tab').forEach(tab => {
                if(tab) tab.classList.remove('active');
            });
            const activeTab = document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`);
            if(activeTab) activeTab.classList.add('active');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = getSavedLanguage() || 'kk';
            switchLanguage(savedLang);

            const activeTabName = '<?php echo $activeTab; ?>';
            if (activeTabName) {
                switchTab(activeTabName);
            }

            // Auto-dismiss success message
            const successMessage = document.querySelector('.success');
            if (successMessage) {
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="language-switcher">
            <button onclick="switchLanguage('kk')" data-lang="kk">🇰🇿 ҚАЗ</button>
            <button onclick="switchLanguage('ru')" data-lang="ru">🇷🇺 РУС</button>
            <button onclick="switchLanguage('en')" data-lang="en">🇬🇧 ENG</button>
        </div>

        <div class="logo">
            <h1 data-translate-key="page_title_login">QuizCard</h1>
            <p data-translate-key="panel_title">Мұғалімдер үшін басқару панелі</p>
        </div>

        <div class="tabs">
            <button class="tab <?php echo $activeTab === 'login' ? 'active' : ''; ?>" onclick="switchTab('login')" data-translate-key="login_tab">Кіру</button>
            <button class="tab <?php echo $activeTab === 'register' ? 'active' : ''; ?>" onclick="switchTab('register')" data-translate-key="register_tab">Тіркелу</button>
        </div>

        <div class="tab-content">
            <?php if ($error_key): ?>
                <div class="error" data-translate-key="<?php echo $error_key; ?>">
                    <?php echo isset($translations['kk'][$error_key]) ? $translations['kk'][$error_key] : 'Unknown error'; ?>
                </div>
            <?php endif; ?>
            <?php if ($success_key): ?>
                <div class="success" data-translate-key="<?php echo $success_key; ?>">
                    <?php echo isset($translations['kk'][$success_key]) ? $translations['kk'][$success_key] : 'Unknown success message'; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div id="login" class="tab-pane <?php echo $activeTab === 'login' ? 'active' : ''; ?>">
                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="username" data-translate-key="username_label">Пайдаланушы аты</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password" data-translate-key="password_label">Құпия сөз</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn" data-translate-key="login_button">Кіру</button>
                </form>
                <div class="student-link">
                    <a href="student_login.php" data-translate-key="student_link">👨‍🎓 Оқушылар үшін кіру</a>
                </div>
            </div>

            <!-- Register Form -->
            <div id="register" class="tab-pane <?php echo $activeTab === 'register' ? 'active' : ''; ?>">
                <form action="login.php" method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="reg_first_name" data-translate-key="first_name_label">Аты</label>
                            <input type="text" id="reg_first_name" name="reg_first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_last_name" data-translate-key="last_name_label">Тегі</label>
                            <input type="text" id="reg_last_name" name="reg_last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reg_email" data-translate-key="email_label">Email</label>
                        <input type="email" id="reg_email" name="reg_email" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_username" data-translate-key="username_label">Пайдаланушы аты</label>
                        <input type="text" id="reg_username" name="reg_username" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password" data-translate-key="password_label">Құпия сөз</label>
                        <input type="password" id="reg_password" name="reg_password" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_confirm_password" data-translate-key="confirm_password_label">Құпия сөзді растау</label>
                        <input type="password" id="reg_confirm_password" name="reg_confirm_password" required>
                    </div>
                    <button type="submit" name="register" class="btn" data-translate-key="register_button">Тіркелу</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
