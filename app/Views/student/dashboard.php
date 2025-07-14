<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель ученика - QuizCard</title>
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
            color: #333;
        }

        .header {
            background: #28a745;
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
            font-weight: 500;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .action-card h3 {
            color: #28a745;
            margin-bottom: 1rem;
        }

        .btn {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>📚 QuizCard - Ученик</h1>
            </div>
            <nav class="nav-links">
                <a href="/student/dashboard">🏠 Главная</a>
                <a href="/student/flashcards">📚 Карточки</a>
                <a href="/student/tests">📝 Тесты</a>
                <a href="/student/vocabulary">📖 Словарь</a>
                <a href="/student/statistics">📊 Статистика</a>
                <a href="/logout">🚪 Выход</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <div class="welcome">
            <h1>👋 Добро пожаловать в панель ученика!</h1>
            <p>Изучайте новые слова и отслеживайте свой прогресс</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['total_words'] ?? 0; ?></div>
                <div class="stat-label">Всего слов</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['learned_words'] ?? 0; ?></div>
                <div class="stat-label">Изучено</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($words_for_review ?? []); ?></div>
                <div class="stat-label">К повторению</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $studying_words ?? 0; ?></div>
                <div class="stat-label">В изучении</div>
            </div>
        </div>

        <div class="actions">
            <div class="action-card">
                <h3>📚 Изучать карточки</h3>
                <p>Изучайте новые слова с помощью карточек</p>
                <a href="/student/flashcards" class="btn">Начать изучение</a>
            </div>
            <div class="action-card">
                <h3>📝 Пройти тест</h3>
                <p>Проверьте свои знания в тестах</p>
                <a href="/student/tests" class="btn">Пройти тест</a>
            </div>
            <div class="action-card">
                <h3>📖 Просмотреть словарь</h3>
                <p>Просматривайте все изученные слова</p>
                <a href="/student/vocabulary" class="btn">Открыть словарь</a>
            </div>
            <div class="action-card">
                <h3>📊 Статистика</h3>
                <p>Отслеживайте свой прогресс</p>
                <a href="/student/statistics" class="btn">Посмотреть статистику</a>
            </div>
        </div>
    </div>
</body>
</html>
