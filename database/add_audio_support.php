<?php
/**
 * Скрипт для добавления поддержки аудиофайлов в базу данных QuizCard
 * Запустите этот файл в браузере для обновления структуры БД
 */

// Подключаем конфигурацию базы данных
require_once '../config/database.php';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление поддержки аудиофайлов - QuizCard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .success {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
        .error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .code {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin: 10px 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎵 Добавление поддержки аудиофайлов</h1>
        
        <?php
        $migration_completed = false;
        $errors = [];
        $success_messages = [];
        
        if (isset($_POST['run_migration'])) {
            try {
                $database = new Database();
                $pdo = $database->getConnection();
                
                // Проверяем, существует ли уже колонка audio_path
                $check_query = "SHOW COLUMNS FROM vocabulary LIKE 'audio_path'";
                $stmt = $pdo->query($check_query);
                
                if ($stmt->rowCount() > 0) {
                    $success_messages[] = "Колонка 'audio_path' уже существует в таблице vocabulary.";
                } else {
                    // Добавляем колонку audio_path
                    $alter_query = "ALTER TABLE vocabulary ADD COLUMN audio_path VARCHAR(500) NULL AFTER image_path";
                    $pdo->exec($alter_query);
                    $success_messages[] = "Колонка 'audio_path' успешно добавлена в таблицу vocabulary.";
                }
                
                // Создаем индекс если его нет
                try {
                    $index_query = "CREATE INDEX idx_vocabulary_audio_path ON vocabulary(audio_path)";
                    $pdo->exec($index_query);
                    $success_messages[] = "Индекс 'idx_vocabulary_audio_path' успешно создан.";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        $success_messages[] = "Индекс 'idx_vocabulary_audio_path' уже существует.";
                    } else {
                        throw $e;
                    }
                }
                
                // Проверяем структуру таблицы
                $structure_query = "DESCRIBE vocabulary";
                $stmt = $pdo->query($structure_query);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $audio_column_found = false;
                foreach ($columns as $column) {
                    if ($column['Field'] === 'audio_path') {
                        $audio_column_found = true;
                        break;
                    }
                }
                
                if ($audio_column_found) {
                    $success_messages[] = "Структура базы данных успешно обновлена!";
                    $migration_completed = true;
                } else {
                    $errors[] = "Ошибка: колонка audio_path не найдена после миграции.";
                }
                
            } catch (PDOException $e) {
                $errors[] = "Ошибка базы данных: " . $e->getMessage();
            } catch (Exception $e) {
                $errors[] = "Общая ошибка: " . $e->getMessage();
            }
        }
        ?>
        
        <?php if (!empty($errors)): ?>
            <div class="step error">
                <h3>❌ Ошибки:</h3>
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_messages)): ?>
            <div class="step success">
                <h3>✅ Успешно выполнено:</h3>
                <?php foreach ($success_messages as $message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$migration_completed && empty($errors)): ?>
            <div class="step">
                <h3>📝 Что будет сделано:</h3>
                <ol>
                    <li>Добавлена колонка <code>audio_path</code> в таблицу <code>vocabulary</code></li>
                    <li>Создан индекс для улучшения производительности</li>
                    <li>Проверена корректность обновления</li>
                </ol>
            </div>
            
            <div class="step warning">
                <h3>⚠️ Важно:</h3>
                <ul>
                    <li>Убедитесь, что у вас есть резервная копия базы данных</li>
                    <li>Миграция безопасна и не затронет существующие данные</li>
                    <li>После успешного обновления файл можно удалить</li>
                </ul>
            </div>
            
            <form method="post" style="text-align: center; margin-top: 30px;">
                <button type="submit" name="run_migration" class="btn">
                    🚀 Запустить миграцию
                </button>
            </form>
        <?php endif; ?>
        
        <?php if ($migration_completed): ?>
            <div class="step success">
                <h3>🎉 Миграция завершена!</h3>
                <p>Теперь система QuizCard поддерживает аудиофайлы для словарных слов.</p>
                <p><strong>Что дальше:</strong></p>
                <ul>
                    <li>Вы можете безопасно удалить этот файл миграции</li>
                    <li>Перейдите в раздел управления словарем для добавления аудиофайлов</li>
                    <li>Поддерживаемые форматы: MP3, WAV, OGG (максимум 3MB, до 30 секунд)</li>
                </ul>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="../teacher/vocabulary.php" class="btn btn-success">
                        📚 Перейти к словарю
                    </a>
                    <a href="../teacher/dashboard.php" class="btn">
                        🏠 На главную
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="step">
            <h3>📋 SQL команды (для справки):</h3>
            <div class="code">
-- Добавление колонки для аудиофайлов<br>
ALTER TABLE vocabulary ADD COLUMN audio_path VARCHAR(500) NULL AFTER image_path;<br><br>
-- Создание индекса<br>
CREATE INDEX idx_vocabulary_audio_path ON vocabulary(audio_path);
            </div>
        </div>
    </div>
</body>
</html>
