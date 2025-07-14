<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Настройка базы данных</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; 
            max-width: 900px; 
            margin: 2rem auto; 
            padding: 2rem; 
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 2rem;
        }
        
        .success { 
            color: #28a745; 
            background: #d4edda;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #c3e6cb;
            margin: 1rem 0;
        }
        
        .error { 
            color: #dc3545; 
            background: #f8d7da;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #f5c6cb;
            margin: 1rem 0;
        }
        
        .warning { 
            color: #856404; 
            background: #fff3cd;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #ffeaa7;
            margin: 1rem 0;
        }
        
        .info { 
            color: #0c5460; 
            background: #d1ecf1;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #bee5eb;
            margin: 1rem 0;
        }
        
        .section { 
            margin: 2rem 0; 
            padding: 1.5rem; 
            border: 1px solid #dee2e6; 
            border-radius: 10px; 
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }
        
        .btn { 
            padding: 0.75rem 1.5rem; 
            background: #667eea; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            display: inline-block; 
            margin: 0.5rem 0.5rem 0.5rem 0; 
            border: none;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-danger { 
            background: #dc3545; 
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        pre { 
            background: #f8f9fa; 
            padding: 1rem; 
            border-radius: 5px; 
            overflow-x: auto; 
            border: 1px solid #e9ecef;
            font-size: 0.9rem;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .status-item {
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .status-ok {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        
        .status-warning {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .status-error {
            background: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        .actions {
            margin: 2rem 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 Настройка базы данных QuizCard</h1>
        <p>Система инициализации и проверки базы данных</p>
    </div>

    <?php if (isset($setup_complete) && $setup_complete): ?>
        <div class="success">
            <h3>✅ Настройка завершена успешно!</h3>
            <p>База данных настроена и готова к работе.</p>
            <div class="actions">
                <a href="/" class="btn btn-success">Перейти к приложению</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Database Connection Status -->
    <div class="section">
        <h2>📊 Статус подключения к базе данных</h2>
        <div class="status-grid">
            <div class="status-item <?php echo isset($db_connected) && $db_connected ? 'status-ok' : 'status-error'; ?>">
                <strong>Подключение:</strong>
                <?php echo isset($db_connected) && $db_connected ? '✅ Успешно' : '❌ Ошибка'; ?>
            </div>
            <div class="status-item <?php echo isset($db_info) ? 'status-ok' : 'status-warning'; ?>">
                <strong>Версия MySQL:</strong>
                <?php echo $db_info['version'] ?? 'Неизвестно'; ?>
            </div>
            <div class="status-item <?php echo isset($db_info) ? 'status-ok' : 'status-warning'; ?>">
                <strong>База данных:</strong>
                <?php echo $db_info['database'] ?? 'Не выбрана'; ?>
            </div>
        </div>
    </div>

    <!-- Tables Status -->
    <div class="section">
        <h2>📋 Статус таблиц</h2>
        <?php if (isset($tables_status)): ?>
            <div class="status-grid">
                <?php foreach ($tables_status as $table => $status): ?>
                    <div class="status-item <?php echo $status ? 'status-ok' : 'status-error'; ?>">
                        <strong><?php echo htmlspecialchars($table); ?>:</strong>
                        <?php echo $status ? '✅ Существует' : '❌ Отсутствует'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="warning">
                <p>Информация о таблицах недоступна. Проверьте подключение к базе данных.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Setup Progress -->
    <div class="section">
        <h2>⚙️ Прогресс установки</h2>
        <?php
        $total_steps = 5;
        $completed_steps = 0;
        if (isset($db_connected) && $db_connected) $completed_steps++;
        if (isset($tables_created) && $tables_created) $completed_steps++;
        if (isset($data_inserted) && $data_inserted) $completed_steps++;
        if (isset($permissions_set) && $permissions_set) $completed_steps++;
        if (isset($config_created) && $config_created) $completed_steps++;
        
        $progress_percentage = ($completed_steps / $total_steps) * 100;
        ?>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%"></div>
        </div>
        <p>Выполнено: <?php echo $completed_steps; ?> из <?php echo $total_steps; ?> шагов (<?php echo round($progress_percentage); ?>%)</p>
    </div>

    <!-- Setup Actions -->
    <div class="section">
        <h2>🚀 Действия по настройке</h2>
        
        <?php if (!isset($db_connected) || !$db_connected): ?>
            <div class="error">
                <p><strong>Ошибка подключения к базе данных!</strong></p>
                <p>Проверьте настройки в файле config/database.php</p>
            </div>
        <?php endif; ?>

        <div class="actions">
            <form method="POST" style="display: inline;">
                <button type="submit" name="test_connection" class="btn">🔍 Проверить подключение</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="create_tables" class="btn">📋 Создать таблицы</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <button type="submit" name="insert_data" class="btn">📝 Вставить тестовые данные</button>
            </form>
            
            <form method="POST" style="display: inline;" onsubmit="return confirm('Это удалит все данные! Вы уверены?')">
                <button type="submit" name="reset_database" class="btn btn-danger">🗑 Сбросить базу данных</button>
            </form>
        </div>
    </div>

    <!-- Configuration Info -->
    <div class="section">
        <h2>⚙️ Конфигурация</h2>
        <div class="info">
            <p><strong>Конфигурационные файлы:</strong></p>
            <ul>
                <li>config/database.php - настройки базы данных</li>
                <li>.env - переменные окружения</li>
                <li>config/upload_config.php - настройки загрузки файлов</li>
                <li>config/email_config.php - настройки email</li>
            </ul>
        </div>
    </div>

    <!-- SQL Output -->
    <?php if (isset($sql_output) && !empty($sql_output)): ?>
        <div class="section">
            <h2>📄 Вывод SQL</h2>
            <pre><?php echo htmlspecialchars($sql_output); ?></pre>
        </div>
    <?php endif; ?>

    <!-- Error Log -->
    <?php if (isset($error_log) && !empty($error_log)): ?>
        <div class="section">
            <h2>🚨 Журнал ошибок</h2>
            <div class="error">
                <pre><?php echo htmlspecialchars($error_log); ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- System Requirements -->
    <div class="section">
        <h2>📋 Системные требования</h2>
        <div class="status-grid">
            <div class="status-item <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'status-ok' : 'status-error'; ?>">
                <strong>PHP версия:</strong>
                <?php echo PHP_VERSION; ?> (требуется ≥7.4.0)
            </div>
            <div class="status-item <?php echo extension_loaded('pdo') ? 'status-ok' : 'status-error'; ?>">
                <strong>PDO:</strong>
                <?php echo extension_loaded('pdo') ? '✅ Доступно' : '❌ Отсутствует'; ?>
            </div>
            <div class="status-item <?php echo extension_loaded('pdo_mysql') ? 'status-ok' : 'status-error'; ?>">
                <strong>PDO MySQL:</strong>
                <?php echo extension_loaded('pdo_mysql') ? '✅ Доступно' : '❌ Отсутствует'; ?>
            </div>
            <div class="status-item <?php echo is_writable('uploads/') ? 'status-ok' : 'status-warning'; ?>">
                <strong>Папка uploads/:</strong>
                <?php echo is_writable('uploads/') ? '✅ Доступна для записи' : '⚠️ Только для чтения'; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds during setup
        if (document.querySelector('.progress-fill').style.width !== '100%') {
            setTimeout(() => {
                window.location.reload();
            }, 30000);
        }
        
        // Form submission feedback
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                button.disabled = true;
                button.textContent = 'Выполняется...';
            });
        });
    </script>
</body>
</html>
