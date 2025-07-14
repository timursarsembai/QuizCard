<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление тестами - QuizCard</title>
    <link rel="stylesheet" href="/css/app.css">
    <style>
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .breadcrumb {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .breadcrumb a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: white;
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
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-primary {
            background: #28a745;
        }

        .btn-primary:hover {
            background: #218838;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card h2 {
            margin-bottom: 1rem;
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .test-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s;
        }

        .test-card:hover {
            transform: translateY(-5px);
        }

        .test-card h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .test-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .test-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .test-actions .btn {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .tests-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🧪 Управление тестами</h1>
                <div class="breadcrumb">
                    <a href="/teacher/decks">Колоды</a> → <span>Тесты колоды</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="/teacher/decks" class="btn">← Назад к колодам</a>
                <a href="/logout" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Alerts -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Deck Info -->
        <div class="card">
            <h2>📚 Колода: <?php echo htmlspecialchars($current_deck['name']); ?></h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $deck_stats['total_words']; ?></div>
                    <div class="stat-label">Всего слов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($tests); ?></div>
                    <div class="stat-label">Тестов создано</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_attempts; ?></div>
                    <div class="stat-label">Всего попыток</div>
                </div>
            </div>
        </div>

        <!-- Create New Test -->
        <div class="card">
            <h2>➕ Создать новый тест</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="test_name">Название теста:</label>
                        <input type="text" id="test_name" name="test_name" required>
                    </div>
                    <div class="form-group">
                        <label for="questions_count">Количество вопросов:</label>
                        <input type="number" id="questions_count" name="questions_count" min="1" max="<?php echo $deck_stats['total_words']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="time_limit">Ограничение по времени (минуты, необязательно):</label>
                        <input type="number" id="time_limit" name="time_limit" min="1">
                    </div>
                </div>
                <button type="submit" name="create_test" class="btn btn-primary">Создать тест</button>
            </form>
        </div>

        <!-- Tests List -->
        <div class="card">
            <h2>📋 Список тестов</h2>
            
            <?php if (empty($tests)): ?>
                <div class="empty-state">
                    <h3>Пока нет тестов</h3>
                    <p>Создайте первый тест для этой колоды</p>
                </div>
            <?php else: ?>
                <div class="tests-grid">
                    <?php foreach ($tests as $test): ?>
                        <div class="test-card">
                            <h3><?php echo htmlspecialchars($test['name']); ?></h3>
                            
                            <div class="test-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $test['questions_count']; ?></div>
                                    <div class="stat-label">Вопросов</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $test['attempts_count'] ?? 0; ?></div>
                                    <div class="stat-label">Попыток</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $test['time_limit'] ? $test['time_limit'] . 'м' : '∞'; ?></div>
                                    <div class="stat-label">Время</div>
                                </div>
                            </div>

                            <div class="test-actions">
                                <a href="/teacher/test_edit?test_id=<?php echo $test['id']; ?>" class="btn btn-info">Редактировать</a>
                                <a href="/teacher/test_preview?test_id=<?php echo $test['id']; ?>" class="btn btn-success">Предпросмотр</a>
                                <a href="/teacher/test_results?test_id=<?php echo $test['id']; ?>" class="btn btn-warning">Результаты</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Вы уверены?')">
                                    <input type="hidden" name="delete_test_id" value="<?php echo $test['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Удалить</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="/js/security.js"></script>
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const questionsCount = parseInt(document.getElementById('questions_count').value);
            const maxWords = <?php echo $deck_stats['total_words']; ?>;
            
            if (questionsCount > maxWords) {
                e.preventDefault();
                alert('Количество вопросов не может превышать количество слов в колоде (' + maxWords + ')');
            }
        });
    </script>
</body>
</html>
