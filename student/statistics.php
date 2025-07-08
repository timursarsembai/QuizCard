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

if (!$user->isLoggedIn() || $user->getRole() !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Получаем статистику по тестам
$test_statistics = $test->getStudentTestStatistics($student_id);

// Получаем последние результаты тестов
$query = "SELECT ta.*, t.name as test_name, d.name as deck_name, d.color as deck_color
          FROM test_attempts ta 
          JOIN tests t ON ta.test_id = t.id 
          JOIN decks d ON t.deck_id = d.id 
          WHERE ta.student_id = :student_id 
          ORDER BY ta.completed_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$recent_test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем статистику по колодам
$assigned_decks = $deck->getDecksForStudent($student_id);

// Получаем общую статистику
$total_words = 0;
$words_to_review = 0;
$learned_words = 0;
$total_reviews = 0;

foreach ($assigned_decks as $deck_item) {
    $total_words += $deck_item['total_words'];
    $words_to_review += $deck_item['words_to_review'];
}

// Получаем статистику изученных слов
$query = "SELECT COUNT(*) as learned_count FROM learning_progress WHERE student_id = :student_id AND repetition_count >= 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$learned_result = $stmt->fetch(PDO::FETCH_ASSOC);
$learned_words = $learned_result['learned_count'];

// Получаем статистику изучаемых слов (начали изучать, но еще не изучили)
$query = "SELECT COUNT(*) as studying_count FROM learning_progress WHERE student_id = :student_id AND total_attempts > 0 AND repetition_count < 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$studying_result = $stmt->fetch(PDO::FETCH_ASSOC);
$studying_words = $studying_result['studying_count'];

// Получаем статистику по тестам
$query = "SELECT 
    COUNT(DISTINCT ta.test_id) as tests_taken,
    COUNT(ta.id) as total_attempts,
    AVG(ta.score) as average_score,
    MAX(ta.score) as best_score,
    SUM(ta.correct_answers) as total_correct,
    SUM(ta.total_questions) as total_questions_answered
    FROM test_attempts ta 
    WHERE ta.student_id = :student_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$test_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем последние результаты тестов
$recent_test_attempts = $test->getStudentRecentAttempts($student_id, 10);

// Получаем статистику по дням за последние 30 дней
$query = "SELECT DATE(updated_at) as review_date, COUNT(*) as reviews_count 
          FROM learning_progress 
          WHERE student_id = :student_id 
            AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            AND repetition_count > 0
          GROUP BY DATE(updated_at)
          ORDER BY review_date DESC
          LIMIT 30";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем топ-5 сложных слов (с наибольшим количеством ошибок)
$query = "SELECT v.foreign_word, v.translation, lp.repetition_count, lp.ease_factor,
                 DATEDIFF(lp.next_review_date, CURDATE()) as days_until_review
          FROM learning_progress lp
          INNER JOIN vocabulary v ON lp.vocabulary_id = v.id
          INNER JOIN deck_assignments da ON v.deck_id = da.deck_id
          WHERE lp.student_id = :student_id AND da.student_id = :student_id
          ORDER BY lp.ease_factor ASC, lp.repetition_count DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$difficult_words = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Рассчитываем процент изученности
$progress_percentage = $total_words > 0 ? round(($learned_words / $total_words) * 100) : 0;

// Данные для графика активности (последние 7 дней)
$activity_data = array_fill(0, 7, 0);
$activity_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $activity_labels[] = date('d.m', strtotime("-$i days"));
    
    foreach ($daily_stats as $stat) {
        if ($stat['review_date'] === $date) {
            $activity_data[6-$i] = $stat['reviews_count'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Статистика</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
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

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(45deg, #667eea, #764ba2);
            height: 100%;
            border-radius: 10px;
            transition: width 0.8s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .chart-container {
            height: 300px;
            position: relative;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .chart-bars {
            display: flex;
            align-items: end;
            height: 200px;
            gap: 10px;
            padding: 20px 0;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, #667eea, #764ba2);
            border-radius: 4px 4px 0 0;
            min-height: 5px;
            position: relative;
            transition: all 0.3s;
        }

        .chart-bar:hover {
            opacity: 0.8;
        }

        .chart-value {
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.8rem;
            font-weight: bold;
            color: #667eea;
        }

        .chart-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.8rem;
            color: #666;
        }

        .words-list {
            display: grid;
            gap: 1rem;
        }

        .word-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
        }

        .word-content {
            flex: 1;
        }

        .word-foreign {
            font-weight: bold;
            color: #333;
        }

        .word-translation {
            color: #666;
            font-size: 0.9rem;
        }

        .word-stats {
            text-align: right;
            font-size: 0.8rem;
            color: #999;
        }

        .deck-stats {
            display: grid;
            gap: 1rem;
        }

        .deck-item {
            display: flex;
            align-items: center;
            padding: 1rem;
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
            margin-bottom: 0.3rem;
        }

        .deck-progress {
            font-size: 0.9rem;
            color: #666;
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

        .test-results {
            display: grid;
            gap: 1rem;
        }

        .test-result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .test-info {
            flex: 1;
        }

        .test-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .test-meta {
            font-size: 0.9rem;
            color: #666;
        }

        .test-score {
            text-align: center;
        }

        .score-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .score-excellent { color: #28a745; }
        .score-good { color: #17a2b8; }
        .score-average { color: #ffc107; }
        .score-poor { color: #dc3545; }

        .score-details {
            font-size: 0.8rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .container {
                padding: 0 1rem;
            }

            .chart-labels {
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>📊 Моя статистика</h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="btn">🏠 Главная</a>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Общая статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?php echo count($assigned_decks); ?></div>
                <div class="stat-label">Колод назначено</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-number"><?php echo $total_words; ?></div>
                <div class="stat-label">Всего слов</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo $learned_words; ?></div>
                <div class="stat-label">Изучено</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">▶️</div>
                <div class="stat-number"><?php echo $studying_words; ?></div>
                <div class="stat-label">Изучается</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔃</div>
                <div class="stat-number"><?php echo $words_to_review; ?></div>
                <div class="stat-label">К повторению</div>
            </div>
        </div>

        <!-- Статистика по тестам -->
        <div class="card">
            <h2>🧪 Статистика тестов</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🧪</div>
                    <div class="stat-number"><?php echo $test_statistics['total_attempts'] ?: 0; ?></div>
                    <div class="stat-label">Тестов пройдено</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo $test_statistics['average_score'] ? number_format($test_statistics['average_score'], 1) . '%' : '0%'; ?></div>
                    <div class="stat-label">Средний балл</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-number"><?php echo $test_statistics['best_score'] ?: 0; ?>%</div>
                    <div class="stat-label">Лучший результат</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-number">
                                <?php 
                                // Проверяем, есть ли у нас числовое значение среднего балла
                                if (isset($test_statistics['average_score']) && is_numeric($test_statistics['average_score'])) {
                                    echo ($test_statistics['average_score'] >= 80) ? 'A' : (($test_statistics['average_score'] >= 60) ? 'B' : 'C');
                                } else {
                                    // Если тестов не было, выводим прочерк или "N/A"
                                    echo '0'; 
                                }
                                ?>
                    </div>
                    <div class="stat-label">Общая оценка</div>
                </div>
            </div>
        </div>

        <!-- Прогресс обучения -->
        <div class="card">
            <h2>Общий прогресс</h2>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%">
                    <?php echo $progress_percentage; ?>%
                </div>
            </div>
            <p>Вы изучили <?php echo $learned_words; ?> из <?php echo $total_words; ?> слов</p>
        </div>

        <!-- График активности за неделю -->
        <div class="card">
            <h2>Активность за последние 7 дней</h2>
            <?php if (array_sum($activity_data) > 0): ?>
                <div class="chart-container">
                    <div class="chart-bars">
                        <?php 
                        $max_value = max($activity_data) ?: 1;
                        foreach ($activity_data as $value): 
                            $height = ($value / $max_value) * 100;
                        ?>
                            <div class="chart-bar" style="height: <?php echo $height; ?>%">
                                <?php if ($value > 0): ?>
                                    <div class="chart-value"><?php echo $value; ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-labels">
                        <?php foreach ($activity_labels as $label): ?>
                            <span><?php echo $label; ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📈 Пока нет данных</h3>
                    <p>Начните изучать слова, чтобы увидеть график активности.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Статистика по колодам -->
        <div class="card">
            <h2>Прогресс по колодам</h2>
            <?php if (!empty($assigned_decks)): ?>
                <div class="deck-stats">
                    <?php foreach ($assigned_decks as $deck_item): 
                        $deck_progress = $deck_item['total_words'] > 0 ? 
                            round((($deck_item['total_words'] - $deck_item['words_to_review']) / $deck_item['total_words']) * 100) : 0;
                    ?>
                        <div class="deck-item" style="border-left-color: <?php echo htmlspecialchars($deck_item['color']); ?>">
                            <div class="deck-info">
                                <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                                <div class="deck-progress">
                                    <?php echo $deck_item['total_words']; ?> слов, 
                                    <?php echo $deck_item['words_to_review']; ?> к изучению
                                    (<?php echo $deck_progress; ?>% завершено)
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>📚 Колоды не назначены</h3>
                    <p>Попросите преподавателя назначить вам колоды для изучения.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Сложные слова -->
        <?php if (!empty($difficult_words)): ?>
            <div class="card">
                <h2>Слова, требующие внимания</h2>
                <div class="words-list">
                    <?php foreach ($difficult_words as $word): ?>
                        <div class="word-item">
                            <div class="word-content">
                                <div class="word-foreign"><?php echo htmlspecialchars($word['foreign_word']); ?></div>
                                <div class="word-translation"><?php echo htmlspecialchars($word['translation']); ?></div>
                            </div>
                            <div class="word-stats">
                                Повторений: <?php echo $word['repetition_count']; ?><br>
                                <?php if ($word['days_until_review'] <= 0): ?>
                                    <span style="color: #dc3545;">Требует изучения</span>
                                <?php else: ?>
                                    Следующее повторение: через <?php echo $word['days_until_review']; ?> дн.
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Последние результаты тестов -->
        <?php if (!empty($recent_test_attempts)): ?>
            <div class="card">
                <h2>📈 Последние результаты тестов</h2>
                <div class="test-results">
                    <?php foreach ($recent_test_attempts as $attempt): ?>
                        <div class="test-result-item">
                            <div class="test-info">
                                <div class="test-name"><?php echo htmlspecialchars($attempt['test_name']); ?></div>
                                <div class="test-meta">
                                    Колода: <?php echo htmlspecialchars($attempt['deck_name']); ?> • 
                                    <?php echo date('d.m.Y H:i', strtotime($attempt['completed_at'])); ?>
                                </div>
                            </div>
                            <div class="test-score">
                                <div class="score-number 
                                    <?php 
                                    $score = $attempt['score'];
                                    if ($score >= 90) echo 'score-excellent';
                                    elseif ($score >= 75) echo 'score-good';
                                    elseif ($score >= 60) echo 'score-average';
                                    else echo 'score-poor';
                                    ?>">
                                    <?php echo round($attempt['score']); ?>%
                                </div>
                                <div class="score-details">
                                    <?php echo $attempt['correct_answers']; ?>/<?php echo $attempt['total_questions']; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="tests.php" class="btn btn-primary">📊 Все тесты</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Анимация прогресс-бара
        document.addEventListener('DOMContentLoaded', function() {
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                const targetWidth = progressFill.style.width;
                progressFill.style.width = '0%';
                setTimeout(() => {
                    progressFill.style.width = targetWidth;
                }, 300);
            }

            // Анимация счетчиков
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                let currentValue = 0;
                const increment = finalValue / 50;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        stat.textContent = finalValue;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(currentValue);
                    }
                }, 20);
            });
        });
    </script>
</body>
</html>
