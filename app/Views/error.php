<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка <?php echo $error_code ?? 500; ?> - QuizCard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .error-container {
            max-width: 600px;
            padding: 2rem;
        }

        .error-code {
            font-size: 6rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .error-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .error-description {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.8;
            line-height: 1.6;
        }

        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
            display: inline-block;
        }

        .btn:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .error-details {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            font-family: monospace;
            font-size: 0.9rem;
            opacity: 0.7;
        }

        .logo {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.6;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-description {
                font-size: 1rem;
            }
            
            .error-container {
                padding: 1rem;
            }
            
            .error-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="logo">📚 QuizCard</div>
        
        <div class="error-code"><?php echo $error_code ?? 500; ?></div>
        
        <h1 class="error-title">
            <?php
            $error_titles = [
                400 => 'Некорректный запрос',
                401 => 'Требуется авторизация',
                403 => 'Доступ запрещён',
                404 => 'Страница не найдена',
                500 => 'Внутренняя ошибка сервера',
                503 => 'Сервис недоступен'
            ];
            echo $error_titles[$error_code ?? 500] ?? 'Произошла ошибка';
            ?>
        </h1>
        
        <p class="error-description">
            <?php
            $error_descriptions = [
                400 => 'Сервер не может обработать ваш запрос из-за неверного синтаксиса.',
                401 => 'Для доступа к этой странице необходимо войти в систему.',
                403 => 'У вас нет прав для доступа к этой странице.',
                404 => 'Запрашиваемая страница не существует или была перемещена.',
                500 => 'На сервере произошла внутренняя ошибка. Мы уже работаем над её устранением.',
                503 => 'Сервис временно недоступен. Попробуйте позже.'
            ];
            echo $error_descriptions[$error_code ?? 500] ?? 'Что-то пошло не так. Попробуйте еще раз.';
            ?>
        </p>
        
        <div class="error-actions">
            <a href="/" class="btn">🏠 На главную</a>
            <a href="javascript:history.back()" class="btn">← Назад</a>
            <?php if (($error_code ?? 500) == 404): ?>
                <a href="/login" class="btn">🔐 Войти</a>
            <?php endif; ?>
        </div>
        
        <?php if (isset($error_message) && !empty($error_message) && (defined('DEBUG') && DEBUG)): ?>
            <div class="error-details">
                <strong>Техническая информация:</strong><br>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Автоматическое перенаправление через 30 секунд для некоторых ошибок
        const errorCode = <?php echo json_encode($error_code ?? 500); ?>;
        
        if ([500, 503].includes(errorCode)) {
            setTimeout(() => {
                if (confirm('Попробовать перезагрузить страницу?')) {
                    window.location.reload();
                }
            }, 30000);
        }
        
        // Горячие клавиши
        document.addEventListener('keydown', function(e) {
            if (e.key === 'h' || e.key === 'H') {
                window.location.href = '/';
            } else if (e.key === 'b' || e.key === 'B') {
                history.back();
            }
        });
    </script>
</body>
</html>
