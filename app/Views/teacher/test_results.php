<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты теста - QuizCard</title>
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
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .test-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid #667eea;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .results-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .results-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .results-table tr:hover {
            background: #f8f9fa;
        }

        .score-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .score-excellent {
            background: #d4edda;
            color: #155724;
        }

        .score-good {
            background: #fff3cd;
            color: #856404;
        }

        .score-average {
            background: #ffeaa7;
            color: #b7652c;
        }

        .score-poor {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .time-display {
            font-family: monospace;
            font-weight: bold;
        }

        .percentage {
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .results-table {
                font-size: 0.9rem;
            }

            .results-table th,
            .results-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>📊 Результаты теста</h1>
                <div class="breadcrumb">
                    <a href="/teacher/decks">Колоды</a> → 
                    <a href="/teacher/test_manager?deck_id=<?php echo $test_info['deck_id']; ?>">Тесты</a> → 
                    <span>Результаты</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="/teacher/test_manager?deck_id=<?php echo $test_info['deck_id']; ?>" class="btn">← Назад</a>
                <a href="/logout" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Test Info -->
        <div class="test-info">
            <h2>🧪 Тест: <?php echo htmlspecialchars($test_info['name']); ?></h2>
            <p><strong>📚 Колода:</strong> <?php echo htmlspecialchars($test_info['deck_name']); ?></p>
            <p><strong>❓ Вопросов:</strong> <?php echo $test_info['questions_count']; ?></p>
            <?php if ($test_info['time_limit']): ?>
                <p><strong>⏱ Ограничение по времени:</strong> <?php echo $test_info['time_limit']; ?> минут</p>
            <?php endif; ?>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['total_attempts']; ?></div>
                <div class="stat-label">Всего попыток</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['unique_students']; ?></div>
                <div class="stat-label">Уникальных учеников</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($statistics['average_score'], 1); ?>%</div>
                <div class="stat-label">Средний балл</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo gmdate("i:s", $statistics['average_time']); ?></div>
                <div class="stat-label">Среднее время</div>
            </div>
        </div>

        <!-- Best Results -->
        <?php if (!empty($best_results)): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="section-title">🏆 Лучшие результаты учеников</div>
                </div>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Ученик</th>
                            <th>Балл</th>
                            <th>Правильных ответов</th>
                            <th>Время</th>
                            <th>Дата прохождения</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($best_results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                <td>
                                    <?php
                                    $percentage = round($result['score']);
                                    $class = '';
                                    if ($percentage >= 85) $class = 'score-excellent';
                                    elseif ($percentage >= 70) $class = 'score-good';
                                    elseif ($percentage >= 50) $class = 'score-average';
                                    else $class = 'score-poor';
                                    ?>
                                    <span class="score-badge <?php echo $class; ?>"><?php echo $percentage; ?>%</span>
                                </td>
                                <td><?php echo $result['correct_answers']; ?> из <?php echo $result['total_questions']; ?></td>
                                <td class="time-display"><?php echo gmdate("i:s", $result['time_taken']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- All Attempts -->
        <?php if (!empty($all_results)): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="section-title">📋 Все попытки</div>
                </div>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Ученик</th>
                            <th>Балл</th>
                            <th>Правильных ответов</th>
                            <th>Время</th>
                            <th>Дата прохождения</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_results as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                <td>
                                    <?php
                                    $percentage = round($result['score']);
                                    $class = '';
                                    if ($percentage >= 85) $class = 'score-excellent';
                                    elseif ($percentage >= 70) $class = 'score-good';
                                    elseif ($percentage >= 50) $class = 'score-average';
                                    else $class = 'score-poor';
                                    ?>
                                    <span class="score-badge <?php echo $class; ?>"><?php echo $percentage; ?>%</span>
                                </td>
                                <td><?php echo $result['correct_answers']; ?> из <?php echo $result['total_questions']; ?></td>
                                <td class="time-display"><?php echo gmdate("i:s", $result['time_taken']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="results-section">
                <div class="empty-state">
                    <h3>Пока нет результатов</h3>
                    <p>Ученики еще не проходили этот тест</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="/js/security.js"></script>
</body>
</html>
