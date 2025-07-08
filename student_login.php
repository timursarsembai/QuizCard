<?php
session_start();

// 1. Централизованные переводы
$translations = [
    'kk' => [
        'page_title' => 'QuizCard - Оқушылар үшін кіру',
        'platform_subtitle' => 'Тіл үйрену платформасы',
        'username_label' => 'Пайдаланушы аты:',
        'username_placeholder' => 'Сіздің пайдаланушы атыңызды енгізіңіз',
        'password_label' => 'Құпия сөз:',
        'password_placeholder' => 'Сіздің құпия сөзіңізді енгізіңіз',
        'login_button' => 'Жүйеге кіру',
        'info_box_title' => '📚 Оқушыларға арналған',
        'info_box_content' => 'Кіру деректерін сіздің мұғаліміңіз береді. Егер аккаунтыңыз болмаса, жасау үшін мұғаліміңізге хабарласыңыз.',
        'teacher_link' => '👨‍🏫 Мұғалімдер үшін кіру',
        'error_db_connection' => 'Дерекқорға қосылу қатесі. <a href="setup.php">ДҚ параметрін тексеріңіз</a>',
        'error_student_only' => 'Бұл бет тек оқушыларға арналған. Мұғалімдер негізгі бет арқылы кіруі керек.',
        'error_invalid_credentials' => 'Жарамсыз логин деректері. Пайдаланушы аты мен құпия сөзді тексеріңіз.',
        'error_system' => 'Жүйе қатесі. Кейінірек қайталап көріңіз.'
    ],
    'ru' => [
        'page_title' => 'QuizCard - Вход для учеников',
        'platform_subtitle' => 'Платформа изучения языков',
        'username_label' => 'Имя пользователя:',
        'username_placeholder' => 'Введите ваше имя пользователя',
        'password_label' => 'Пароль:',
        'password_placeholder' => 'Введите ваш пароль',
        'login_button' => 'Войти в систему',
        'info_box_title' => '📚 Для учеников',
        'info_box_content' => 'Данные для входа предоставляет ваш преподаватель. Если у вас нет аккаунта, обратитесь к преподавателю для его создания.',
        'teacher_link' => '👨‍🏫 Вход для преподавателей',
        'error_db_connection' => 'Ошибка подключения к базе данных. <a href="setup.php">Проверить настройку БД</a>',
        'error_student_only' => 'Эта страница предназначена только для учеников. Преподаватели должны войти через главную страницу.',
        'error_invalid_credentials' => 'Неверные данные для входа. Проверьте имя пользователя и пароль.',
        'error_system' => 'Ошибка системы. Попробуйте позже.'
    ],
    'en' => [
        'page_title' => 'QuizCard - Student Login',
        'platform_subtitle' => 'Language Learning Platform',
        'username_label' => 'Username:',
        'username_placeholder' => 'Enter your username',
        'password_label' => 'Password:',
        'password_placeholder' => 'Enter your password',
        'login_button' => 'Sign In',
        'info_box_title' => '📚 For Students',
        'info_box_content' => 'Login credentials are provided by your teacher. If you don\'t have an account, contact your teacher to create one.',
        'teacher_link' => '👨‍🏫 Teacher Login',
        'error_db_connection' => 'Database connection error. <a href="setup.php">Check DB setup</a>',
        'error_student_only' => 'This page is for students only. Teachers should log in through the main page.',
        'error_invalid_credentials' => 'Invalid login details. Check your username and password.',
        'error_system' => 'System error. Please try again later.'
    ]
];

$error_key = '';

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

            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if ($user->login($username, $password)) {
                if ($user->getRole() === 'student') {
                    header("Location: student/dashboard.php");
                    exit();
                } else {
                    $error_key = 'error_student_only';
                }
            } else {
                $error_key = 'error_invalid_credentials';
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
    <title data-translate-key="page_title">QuizCard - Оқушылар үшін кіру</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            color: #4facfe;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #666;
            font-size: 0.95rem;
        }

        .student-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
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

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #4facfe;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
        }

        .error {
            background: #ffe6e6;
            color: #d00;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #d00;
        }

        .teacher-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .teacher-link a {
            color: #4facfe;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .teacher-link a:hover {
            color: #369bfc;
        }

        .info-box {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            text-align: center;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .info-box p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .login-container {
                padding: 2rem;
            }

            .logo h1 {
                font-size: 1.8rem;
            }

            .student-icon {
                font-size: 3rem;
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
            color: #4facfe;
        }
        
        .language-switcher button.active {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .language-switcher button:hover:not(.active) {
            background: rgba(79, 172, 254, 0.1);
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
        // 2. Встраиваем переводы в JSON
        const translations = <?php echo json_encode($translations); ?>;
        const errorKey = '<?php echo $error_key; ?>';
        let currentLang = 'kk'; // Default to Kazakh

        function switchLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('selectedLanguage', lang);

            // Обновляем язык документа и заголовок
            document.documentElement.lang = lang;
            document.title = translations[lang]['page_title'];

            // Обновляем активную кнопку
            document.querySelectorAll('.language-switcher button').forEach(btn => {
                btn.classList.remove('active');
            });
            const selectedBtn = document.querySelector(`[data-lang-btn="${lang}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('active');
            }

            // 3. Динамически обновляем текст на странице
            document.querySelectorAll('[data-translate-key]').forEach(el => {
                const key = el.getAttribute('data-translate-key');
                if (translations[lang][key]) {
                    if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
                        el.placeholder = translations[lang][key];
                    } else {
                        el.innerHTML = translations[lang][key];
                    }
                }
            });
            
            // Обновляем сообщение об ошибке, если оно есть
            const errorElement = document.querySelector('.error');
            if (errorElement && errorKey && translations[lang][errorKey]) {
                errorElement.innerHTML = translations[lang][errorKey];
            }
        }

        function getSavedLanguage() {
            const savedLang = localStorage.getItem('selectedLanguage');
            if (savedLang && ['kk', 'ru', 'en'].includes(savedLang)) {
                return savedLang;
            }
            return 'kk'; // Default to Kazakh
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedLanguage = getSavedLanguage();
            switchLanguage(savedLanguage); // Устанавливаем язык при загрузке

            // Animation for the container
            const container = document.querySelector('.login-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Автоматическое скрытие сообщений об ошибках через 5 секунд
            setTimeout(function() {
                const errorElement = document.querySelector('.error');
                if (errorElement) {
                    errorElement.style.opacity = '0';
                    errorElement.style.transition = 'opacity 0.5s';
                    setTimeout(() => errorElement.remove(), 500);
                }
            }, 5000);
        });
    </script>
</head>
<body>
    <!-- Language Switcher -->
    <div class="language-switcher">
        <button data-lang-btn="kk" onclick="switchLanguage('kk')" class="active">🇰🇿 ҚАЗ</button>
        <button data-lang-btn="ru" onclick="switchLanguage('ru')">🇷🇺 РУС</button>
        <button data-lang-btn="en" onclick="switchLanguage('en')">🇬🇧 ENG</button>
    </div>
    <div class="login-container">
        <!-- 4. Единая HTML-структура -->
        <div class="logo">
            <div class="student-icon">👨‍🎓</div>
            <h1>QuizCard</h1>
            <p data-translate-key="platform_subtitle">Тіл үйрену платформасы</p>
        </div>

        <?php if ($error_key): ?>
            <div class="error" data-translate-key="<?php echo $error_key; ?>">
                <?php echo $translations['ru'][$error_key]; // Отображаем ошибку на русском по умолчанию ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username" data-translate-key="username_label">Пайдаланушы аты:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Сіздің пайдаланушы атыңызды енгізіңіз"
                       data-translate-key="username_placeholder">
            </div>

            <div class="form-group">
                <label for="password" data-translate-key="password_label">Құпия сөз:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Сіздің құпия сөзіңізді енгізіңіз"
                       data-translate-key="password_placeholder">
            </div>

            <button type="submit" class="btn" data-translate-key="login_button">Жүйеге кіру</button>
        </form>

        <div class="info-box">
            <h3 data-translate-key="info_box_title">📚 Оқушыларға арналған</h3>
            <p data-translate-key="info_box_content">Кіру деректерін сіздің мұғаліміңіз береді. Егер аккаунтыңыз болмаса, жасау үшін мұғаліміңізге хабарласыңыз.</p>
        </div>

        <div class="teacher-link">
            <a href="login.php" data-translate-key="teacher_link">👨‍🏫 Мұғалімдер үшін кіру</a>
        </div>
    </div>
</body>
</html>
