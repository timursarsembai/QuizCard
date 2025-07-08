<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../classes/Vocabulary.php';
require_once '../classes/Test.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);
$vocabulary = new Vocabulary($db);
$test = new Test($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Проверяем, что student_id принадлежит данному преподавателю
if (!isset($_GET['student_id'])) {
    header("Location: dashboard.php");
    exit();
}

$student_id = $_GET['student_id'];
$student_info = $user->getStudentInfo($student_id, $teacher_id);

if (!$student_info) {
    header("Location: dashboard.php");
    exit();
}

// Обработка сброса прогресса по колоде
if ($_POST && isset($_POST['reset_deck_progress'])) {
    $deck_id = $_POST['deck_id'];
    if ($vocabulary->resetDeckProgress($student_id, $deck_id, $teacher_id)) {
        $success = "Прогресс по колоде успешно сброшен!";
    } else {
        $error = "Ошибка при сбросе прогресса по колоде";
    }
}

// Обработка полного сброса прогресса
if ($_POST && isset($_POST['reset_all_progress'])) {
    // Сбрасываем прогресс по словам
    $vocabulary_reset = $vocabulary->resetStudentProgress($student_id, $teacher_id);
    
    // Сбрасываем прогресс по тестам
    $tests_reset = $test->resetStudentTestProgress($student_id, $teacher_id);
    
    if ($vocabulary_reset && $tests_reset) {
        $success = "Весь прогресс ученика успешно сброшен (слова и тесты)!";
    } else {
        $error = "Ошибка при полном сбросе прогресса";
    }
}

$student_decks = $deck->getStudentDeckStats($student_id, $teacher_id);

// Получаем статистику по тестам ученика
$test_statistics = $test->getStudentTestStatistics($student_id);

// Получаем результаты по всем тестам ученика
$all_test_results = [];
foreach ($student_decks as $deck_item) {
    $deck_tests = $test->getTestsByDeck($deck_item['id']);
    foreach ($deck_tests as $test_item) {
        $test_stats = $test->getStudentTestStats($test_item['id'], $student_id);
        if ($test_stats['attempts_count'] > 0) {
            $all_test_results[] = [
                'test' => $test_item,
                'deck' => $deck_item,
                'stats' => $test_stats
            ];
        }
    }
}

// Получаем последние попытки
$recent_attempts = $test->getStudentRecentAttempts($student_id, 10);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Управление прогрессом</title>
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
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .student-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .student-avatar {
            font-size: 3rem;
        }

        .student-details h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .student-details p {
            color: #666;
            font-size: 0.9rem;
        }

        .deck-list {
            display: grid;
            gap: 1rem;
        }

        .deck-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid;
        }

        .deck-info {
            flex: 1;
        }

        .deck-name {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .deck-stats {
            font-size: 0.9rem;
            color: #666;
        }

        .deck-progress {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            margin: 0.5rem 0;
            overflow: hidden;
        }

        .deck-progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .deck-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Стили для раздела тестов */
        .test-stats-overview {
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .test-results-section, .recent-attempts-section {
            margin-top: 2rem;
        }

        .test-results-section h3, .recent-attempts-section h3 {
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .test-result-item, .attempt-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }

        .test-result-header, .attempt-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .test-name strong, .attempt-test strong {
            color: #333;
            font-size: 1.1rem;
        }

        .test-name small, .attempt-test small {
            display: block;
            color: #666;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .score-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .score-excellent {
            background: #d4edda;
            color: #155724;
        }

        .score-good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .score-average {
            background: #fff3cd;
            color: #856404;
        }

        .score-poor {
            background: #f8d7da;
            color: #721c24;
        }

        .test-result-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-label {
            color: #666;
            font-size: 0.9rem;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        .attempt-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #666;
        }

        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 1.5rem;
            background: #fff5f5;
        }

        .danger-zone h3 {
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .danger-zone p {
            color: #666;
            margin-bottom: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .empty-state h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
            }

            .container {
                padding: 0 1rem;
            }

            .deck-item {
                flex-direction: column;
                gap: 1rem;
            }

            .deck-actions {
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .test-result-details {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .test-result-header, .attempt-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .attempt-details {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🔄 Управление прогрессом</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="btn">👥 Ученики</a>
                <a href="decks.php" class="btn">📚 Колоды</a>
                <a href="tests.php" class="btn">🎯 Тесты</a>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Информация об ученике</h2>
            <div class="student-info">
                <div class="student-avatar">👨‍🎓</div>
                <div class="student-details">
                    <h3><?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></h3>
                    <p>Имя пользователя: @<?php echo htmlspecialchars($student_info['username']); ?></p>
                    <p>Дата регистрации: <?php echo date('d.m.Y', strtotime($student_info['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Прогресс по колодам</h2>
            <?php if (!empty($student_decks)): ?>
                <div class="deck-list">
                    <?php foreach ($student_decks as $deck_item): 
                        $progress = $deck_item['total_words'] > 0 ? 
                            (($deck_item['total_words'] - $deck_item['words_to_review']) / $deck_item['total_words']) * 100 : 0;
                    ?>
                        <div class="deck-item" style="border-left-color: <?php echo htmlspecialchars($deck_item['color']); ?>">
                            <div class="deck-info">
                                <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                                <div class="deck-stats">
                                    Всего слов: <?php echo $deck_item['total_words']; ?> | 
                                    К изучению: <?php echo $deck_item['words_to_review']; ?> | 
                                    Изучено: <?php echo $deck_item['learned_words']; ?>
                                </div>
                                <div class="deck-progress">
                                    <div class="deck-progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </div>
                            <div class="deck-actions">
                                <form method="POST" action="" style="display: inline;" 
                                      onsubmit="return confirm('Вы уверены, что хотите сбросить прогресс по этой колоде?')">
                                    <input type="hidden" name="deck_id" value="<?php echo $deck_item['id']; ?>">
                                    <button type="submit" name="reset_deck_progress" class="btn btn-warning" title="Сбросить прогресс по колоде">
                                        🔄 Сбросить
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📚 Колоды не назначены</h3>
                    <p>Ученику не назначены колоды для изучения.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📊 Прогресс по тестам</h2>
            
            <!-- Общая статистика по тестам -->
            <?php if ($test_statistics && $test_statistics['total_attempts'] > 0): ?>
                <div class="test-stats-overview">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $test_statistics['total_attempts']; ?></div>
                            <div class="stat-label">Всего попыток</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo round($test_statistics['average_score'], 1); ?>%</div>
                            <div class="stat-label">Средний балл</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $test_statistics['best_score']; ?>%</div>
                            <div class="stat-label">Лучший результат</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($all_test_results); ?></div>
                            <div class="stat-label">Пройдено тестов</div>
                        </div>
                    </div>
                </div>

                <!-- Результаты по отдельным тестам -->
                <?php if (!empty($all_test_results)): ?>
                    <div class="test-results-section">
                        <h3>Результаты по тестам</h3>
                        <div class="test-results-list">
                            <?php foreach ($all_test_results as $result): ?>
                                <div class="test-result-item">
                                    <div class="test-result-header">
                                        <div class="test-name">
                                            <strong><?php echo htmlspecialchars($result['test']['name']); ?></strong>
                                            <small>Колода: <?php echo htmlspecialchars($result['deck']['name']); ?></small>
                                        </div>
                                        <div class="test-best-score">
                                            <span class="score-badge 
                                                <?php 
                                                $score = $result['stats']['best_score'];
                                                if ($score >= 90) echo 'score-excellent';
                                                elseif ($score >= 75) echo 'score-good';
                                                elseif ($score >= 60) echo 'score-average';
                                                else echo 'score-poor';
                                                ?>">
                                                <?php echo $score; ?>%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="test-result-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Попыток:</span>
                                            <span class="detail-value"><?php echo $result['stats']['attempts_count']; ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Средний балл:</span>
                                            <span class="detail-value"><?php echo round($result['stats']['average_score'], 1); ?>%</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Последняя попытка:</span>
                                            <span class="detail-value">
                                                <?php echo date('d.m.Y H:i', strtotime($result['stats']['last_attempt'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Последние попытки -->
                <?php if (!empty($recent_attempts)): ?>
                    <div class="recent-attempts-section">
                        <h3>Последние попытки</h3>
                        <div class="attempts-list">
                            <?php foreach (array_slice($recent_attempts, 0, 5) as $attempt): ?>
                                <div class="attempt-item">
                                    <div class="attempt-info">
                                        <div class="attempt-test">
                                            <strong><?php echo htmlspecialchars($attempt['test_name']); ?></strong>
                                            <small><?php echo htmlspecialchars($attempt['deck_name']); ?></small>
                                        </div>
                                        <div class="attempt-score">
                                            <span class="score-badge 
                                                <?php 
                                                if ($attempt['score'] >= 90) echo 'score-excellent';
                                                elseif ($attempt['score'] >= 75) echo 'score-good';
                                                elseif ($attempt['score'] >= 60) echo 'score-average';
                                                else echo 'score-poor';
                                                ?>">
                                                <?php echo $attempt['score']; ?>%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="attempt-details">
                                        <span><?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?> правильных</span>
                                        <span><?php echo date('d.m.Y H:i', strtotime($attempt['completed_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📝 Тесты не пройдены</h3>
                    <p>Ученик еще не проходил тесты.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="danger-zone">
                <h3>⚠️ Опасная зона</h3>
                <p>Полный сброс прогресса удалит ВСЕ данные об изучении слов и результаты тестов этого ученика. 
                   Это действие нельзя отменить!</p>
                <form method="POST" action="" 
                      onsubmit="return confirm('ВНИМАНИЕ! Вы собираетесь полностью сбросить весь прогресс ученика (включая слова и тесты). Все данные об изучении будут потеряны безвозвратно. Вы уверены?')">
                    <button type="submit" name="reset_all_progress" class="btn btn-danger">
                        🗑️ Сбросить весь прогресс (слова и тесты)
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Автоматическое скрытие уведомлений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
