<?php
$error_code = http_response_code();
if (!$error_code) {
    $error_code = 500;
}

$error_messages = [
    400 => 'Некорректный запрос',
    401 => 'Требуется авторизация',
    403 => 'Доступ запрещён',
    404 => 'Страница не найдена',
    500 => 'Внутренняя ошибка сервера'
];

$error_title = $error_messages[$error_code] ?? 'Ошибка';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка <?php echo $error_code; ?> - QuizCard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .error-code {
            font-size: 6rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .error-description {
            color: #7f8c8d;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $error_code; ?></div>
        <h1 class="error-title"><?php echo $error_title; ?></h1>
        <p class="error-description">
            <?php if ($error_code == 404): ?>
                Запрашиваемая страница не существует или была перемещена.
            <?php elseif ($error_code == 403): ?>
                У вас нет прав доступа к данному ресурсу.
            <?php elseif ($error_code == 500): ?>
                На сервере произошла ошибка. Попробуйте позже.
            <?php else: ?>
                Произошла ошибка при обработке запроса.
            <?php endif; ?>
        </p>
        <a href="/" class="btn">На главную</a>
    </div>
</body>
</html>
