<?php
session_start();

$error = '';

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

            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if ($user->login($username, $password)) {
                if ($user->getRole() === 'student') {
                    header("Location: student/dashboard.php");
                    exit();
                } else {
                    $error = 'Эта страница предназначена только для учеников. Преподаватели должны войти через главную страницу.';
                }
            } else {
                $error = 'Неверные данные для входа. Проверьте имя пользователя и пароль.';
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
    <title>QuizCard - Вход для учеников</title>
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="student-icon">👨‍🎓</div>
            <h1>QuizCard</h1>
            <p>Платформа изучения языков</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Введите ваше имя пользователя">
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Введите ваш пароль">
            </div>

            <button type="submit" class="btn">Войти в систему</button>
        </form>

        <div class="info-box">
            <h3>📚 Для учеников</h3>
            <p>Данные для входа предоставляет ваш преподаватель. Если у вас нет аккаунта, обратитесь к преподавателю для его создания.</p>
        </div>

        <div class="teacher-link">
            <a href="index.php">👨‍🏫 Вход для преподавателей</a>
        </div>
    </div>

    <script>
        // Автоматическое скрытие сообщений об ошибках через 5 секунд
        setTimeout(function() {
            const errorElement = document.querySelector('.error');
            if (errorElement) {
                errorElement.style.opacity = '0';
                errorElement.style.transition = 'opacity 0.5s';
                setTimeout(() => errorElement.remove(), 500);
            }
        }, 5000);

        // Анимация появления формы
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.login-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>
