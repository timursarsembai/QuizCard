<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Руководство по интеграции языков - QuizCard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        .header {
            background: #667eea;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .guide-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .guide-card h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .step {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }

        .step h3 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .code {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            overflow-x: auto;
        }

        .highlight {
            background: #fff3cd;
            padding: 0.75rem;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>📖 Руководство по интеграции языков</h1>
            </div>
            <div class="nav-links">
                <a href="/teacher/dashboard" class="btn">← Назад к панели</a>
                <a href="/logout" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="guide-card">
            <h2>🌐 Пошаговое руководство по добавлению поддержки языков</h2>
            
            <div class="step">
                <h3>Шаг 1: Подключение переводов</h3>
                <p>Добавьте после всех require_once в начале файла:</p>
                <div class="code">require_once '../includes/translations.php';</div>
            </div>

            <div class="step">
                <h3>Шаг 2: Добавление атрибутов перевода</h3>
                <p>В HTML разметке добавьте атрибуты data-translate-key для элементов:</p>
                <div class="code">
&lt;h2 data-translate-key="page_title"&gt;Заголовок страницы&lt;/h2&gt;
&lt;p data-translate-key="description"&gt;Описание&lt;/p&gt;
&lt;button data-translate-key="button_text"&gt;Кнопка&lt;/button&gt;
                </div>
            </div>

            <div class="step">
                <h3>Шаг 3: Placeholder атрибуты</h3>
                <p>Для полей ввода добавьте data-translate-key:</p>
                <div class="code">
&lt;input type="text" data-translate-key="placeholder_key" placeholder="Текст"&gt;
                </div>
            </div>

            <div class="step">
                <h3>Шаг 4: Confirm диалоги</h3>
                <p>Для подтверждающих диалогов добавьте data-confirm-key:</p>
                <div class="code">
&lt;a onclick="return confirm('Вы уверены?')" data-confirm-key="confirm_key"&gt;Удалить&lt;/a&gt;
                </div>
            </div>

            <div class="step">
                <h3>Шаг 5: Включение переключателя языков</h3>
                <p>В контейнер с основным содержимым добавьте:</p>
                <div class="code">
&lt;div class="container"&gt;
    &lt;?php include 'language_switcher.php'; ?&gt;
    &lt;!-- Остальное содержимое --&gt;
&lt;/div&gt;
                </div>
            </div>

            <div class="step">
                <h3>Шаг 6: Добавление переводов</h3>
                <p>Добавьте переводы в файл /includes/translations.php:</p>
                <div class="code">
'page_title' => 'Заголовок страницы',
'description' => 'Описание',
'button_text' => 'Кнопка',
'placeholder_key' => 'Текст placeholder',
'confirm_key' => 'Подтверждение',
                </div>
            </div>

            <div class="highlight">
                <strong>💡 Совет:</strong> Для заголовков страниц (tests_title, students_title, account_title) переводы уже добавлены в систему.
            </div>

            <div class="step">
                <h3>Шаг 7: Тестирование</h3>
                <p>После внесения изменений:</p>
                <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                    <li>Проверьте работу переключателя языков</li>
                    <li>Убедитесь, что все элементы переводятся</li>
                    <li>Проверьте сохранение выбранного языка</li>
                </ul>
            </div>
        </div>

        <div class="guide-card">
            <h2>🎯 Поддерживаемые языки</h2>
            <ul style="margin-left: 2rem;">
                <li><strong>🇰🇿 Казахский (kk)</strong> - қазақ тілі</li>
                <li><strong>🇷🇺 Русский (ru)</strong> - русский язык</li>
                <li><strong>🇬🇧 Английский (en)</strong> - English</li>
            </ul>
        </div>
    </div>
</body>
</html>
