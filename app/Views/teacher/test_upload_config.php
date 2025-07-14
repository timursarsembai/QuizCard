<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конфигурация загрузки тестов - QuizCard</title>
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

        .config-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .config-card h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .config-section {
            margin-bottom: 2rem;
        }

        .config-section h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .config-list {
            list-style: none;
            padding: 0;
        }

        .config-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }

        .config-list li:last-child {
            border-bottom: none;
        }

        .config-value {
            font-weight: bold;
            color: #28a745;
        }

        .status-check {
            padding: 0.75rem;
            border-radius: 5px;
            margin: 0.5rem 0;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .test-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>⚙️ Конфигурация загрузки</h1>
            </div>
            <div class="nav-links">
                <a href="/teacher/dashboard" class="btn">← Назад к панели</a>
                <a href="/logout" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="config-card">
            <h2>📊 Тестирование конфигурации загрузки изображений</h2>
            
            <div class="config-section">
                <h3>📋 Настройки загрузки</h3>
                <ul class="config-list">
                    <li>
                        <span>Максимальный размер файла:</span>
                        <span class="config-value"><?php echo isset($max_size) ? ($max_size / 1024 / 1024) . ' MB' : '5 MB'; ?></span>
                    </li>
                    <li>
                        <span>Разрешенные расширения:</span>
                        <span class="config-value"><?php echo isset($allowed_extensions) ? implode(', ', $allowed_extensions) : 'jpg, jpeg, png, gif, webp'; ?></span>
                    </li>
                    <li>
                        <span>Разрешенные MIME-типы:</span>
                        <span class="config-value"><?php echo isset($allowed_mime_types) ? implode(', ', $allowed_mime_types) : 'image/jpeg, image/png, image/gif'; ?></span>
                    </li>
                    <li>
                        <span>Директория загрузки:</span>
                        <span class="config-value"><?php echo isset($upload_dir) ? $upload_dir : '../uploads/vocabulary/'; ?></span>
                    </li>
                </ul>
            </div>

            <div class="config-section">
                <h3>🔧 Системные настройки PHP</h3>
                <ul class="config-list">
                    <li>
                        <span>upload_max_filesize:</span>
                        <span class="config-value"><?php echo ini_get('upload_max_filesize'); ?></span>
                    </li>
                    <li>
                        <span>post_max_size:</span>
                        <span class="config-value"><?php echo ini_get('post_max_size'); ?></span>
                    </li>
                    <li>
                        <span>max_file_uploads:</span>
                        <span class="config-value"><?php echo ini_get('max_file_uploads'); ?></span>
                    </li>
                    <li>
                        <span>memory_limit:</span>
                        <span class="config-value"><?php echo ini_get('memory_limit'); ?></span>
                    </li>
                </ul>
            </div>

            <div class="config-section">
                <h3>✅ Проверка системы</h3>
                
                <div class="test-section">
                    <h4>📁 Проверка директорий</h4>
                    <?php
                    $upload_dir = '../uploads/vocabulary/';
                    if (is_dir($upload_dir) && is_writable($upload_dir)) {
                        echo '<div class="status-check status-success">✅ Директория загрузки существует и доступна для записи</div>';
                    } else {
                        echo '<div class="status-check status-error">❌ Директория загрузки недоступна или не существует</div>';
                    }
                    ?>
                </div>

                <div class="test-section">
                    <h4>📸 Проверка поддержки изображений</h4>
                    <?php
                    if (extension_loaded('gd') || extension_loaded('imagick')) {
                        echo '<div class="status-check status-success">✅ Расширения для работы с изображениями загружены</div>';
                    } else {
                        echo '<div class="status-check status-warning">⚠️ Расширения GD или ImageMagick не найдены</div>';
                    }
                    ?>
                </div>

                <div class="test-section">
                    <h4>🔒 Проверка безопасности</h4>
                    <?php
                    if (ini_get('file_uploads')) {
                        echo '<div class="status-check status-success">✅ Загрузка файлов разрешена</div>';
                    } else {
                        echo '<div class="status-check status-error">❌ Загрузка файлов отключена в конфигурации PHP</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="config-section">
                <h3>🧪 Тестовые сценарии</h3>
                <div class="test-section">
                    <p><strong>Тест 1:</strong> Генерация уникального имени файла</p>
                    <?php
                    $test_filename = "test_image.jpg";
                    $generated_name = uniqid() . '_' . $test_filename;
                    echo "<div class=\"status-check status-success\">✅ Сгенерировано имя: <code>$generated_name</code></div>";
                    ?>
                </div>

                <div class="test-section">
                    <p><strong>Тест 2:</strong> Проверка размеров файлов</p>
                    <?php
                    $upload_max = ini_get('upload_max_filesize');
                    $post_max = ini_get('post_max_size');
                    
                    if (intval($upload_max) >= 5 && intval($post_max) >= 5) {
                        echo '<div class="status-check status-success">✅ Настройки позволяют загружать файлы до 5MB</div>';
                    } else {
                        echo '<div class="status-check status-warning">⚠️ Рекомендуется увеличить лимиты загрузки</div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="config-card">
            <h2>📝 Рекомендации</h2>
            <ul style="margin-left: 2rem; color: #666;">
                <li>Регулярно проверяйте размер папки uploads</li>
                <li>Используйте сжатие изображений для экономии места</li>
                <li>Настройте резервное копирование загруженных файлов</li>
                <li>Проверяйте логи на наличие ошибок загрузки</li>
            </ul>
        </div>
    </div>
</body>
</html>
