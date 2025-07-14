<?php
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
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('statistics_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card.vocabulary {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .stat-card.tests {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card.progress {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #333;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .stat-sublabel {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 0.3rem;
        }

        .progress-ring {
            display: inline-block;
            position: relative;
            margin-bottom: 1rem;
        }

        .progress-ring svg {
            transform: rotate(-90deg);
        }

        .progress-ring circle {
            fill: transparent;
            stroke-width: 8;
        }

        .progress-ring .background {
            stroke: rgba(255, 255, 255, 0.2);
        }

        .progress-ring .progress {
            stroke: white;
            stroke-linecap: round;
            transition: stroke-dasharray 0.5s ease;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .deck-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .deck-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            background: white;
        }

        .deck-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .deck-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .deck-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .deck-progress {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .progress-item {
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .progress-number {
            font-weight: 600;
            color: #007bff;
            font-size: 1.1rem;
        }

        .progress-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.2rem;
        }

        .deck-progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }

        .deck-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.5s ease;
        }

        .test-results {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .test-result-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .test-result-item:last-child {
            border-bottom: none;
        }

        .test-info {
            flex: 1;
        }

        .test-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .test-deck {
            font-size: 0.9rem;
            color: #666;
            display: flex;
            align-items: center;
        }

        .test-deck .deck-color {
            margin-right: 6px;
        }

        .test-date {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.2rem;
        }

        .test-score {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
        }

        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #cce7ff; color: #004085; }
        .score-average { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }

        .daily-activity {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 0.3rem;
            margin-bottom: 1rem;
        }

        .day-item {
            aspect-ratio: 1;
            background: #f0f0f0;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            position: relative;
        }

        .day-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .day-date {
            font-weight: 600;
        }

        .day-count {
            font-size: 0.6rem;
            opacity: 0.8;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .deck-stats {
                grid-template-columns: 1fr;
            }
            
            .daily-activity {
                grid-template-columns: repeat(7, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>QuizCard</h1>
            </div>
            <nav class="nav-menu">
                <a href="/student/dashboard" data-translate-key="dashboard"><?php echo translate('dashboard'); ?></a>
                <a href="/student/tests" data-translate-key="tests"><?php echo translate('tests'); ?></a>
                <a href="/student/flashcards" data-translate-key="flashcards"><?php echo translate('flashcards'); ?></a>
                <a href="/student/statistics" class="active" data-translate-key="statistics"><?php echo translate('statistics'); ?></a>
                <a href="/student/vocabulary-view" data-translate-key="vocabulary"><?php echo translate('vocabulary'); ?></a>
                <a href="/logout" data-translate-key="logout"><?php echo translate('logout'); ?></a>
            </nav>
            <?php include __DIR__ . '/language_switcher.php'; ?>
        </div>
    </div>

    <div class="container">
        <header class="page-header">
            <h1 data-translate-key="statistics_title"><?php echo translate('statistics_title'); ?></h1>
            <p data-translate-key="statistics_subtitle"><?php echo translate('statistics_subtitle'); ?></p>
        </header>

        <div class="stats-overview">
            <div class="stat-card vocabulary">
                <div class="stat-number"><?php echo $learned_words; ?></div>
                <div class="stat-label" data-translate-key="words_learned"><?php echo translate('words_learned'); ?></div>
                <div class="stat-sublabel">
                    <span data-translate-key="stats_progress_text" 
                          data-learned="<?php echo $learned_words; ?>" 
                          data-total="<?php echo $total_words; ?>">
                        <?php echo sprintf(translate('stats_progress_text'), $learned_words, $total_words); ?>
                    </span>
                </div>
            </div>

            <div class="stat-card tests">
                <div class="stat-number"><?php echo $test_stats['tests_taken'] ?: 0; ?></div>
                <div class="stat-label" data-translate-key="tests_completed"><?php echo translate('tests_completed'); ?></div>
                <div class="stat-sublabel">
                    <?php echo translate('average_score'); ?>: <?php echo round($test_stats['average_score'] ?: 0, 1); ?>%
                </div>
            </div>

            <div class="stat-card progress">
                <div class="progress-ring">
                    <svg width="80" height="80">
                        <circle cx="40" cy="40" r="36" class="background"></circle>
                        <circle cx="40" cy="40" r="36" class="progress" 
                                stroke-dasharray="<?php echo ($total_words > 0) ? round(($learned_words / $total_words) * 226, 2) : 0; ?> 226"></circle>
                    </svg>
                </div>
                <div class="stat-number"><?php echo $total_words > 0 ? round(($learned_words / $total_words) * 100, 1) : 0; ?>%</div>
                <div class="stat-label" data-translate-key="learning_progress"><?php echo translate('learning_progress'); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-number"><?php echo $studying_words; ?></div>
                <div class="stat-label" data-translate-key="words_studying"><?php echo translate('words_studying'); ?></div>
                <div class="stat-sublabel">
                    <?php echo translate('words_to_review'); ?>: <?php echo $words_to_review; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($assigned_decks)): ?>
            <div class="card">
                <h2 class="section-title" data-translate-key="decks_progress"><?php echo translate('decks_progress'); ?></h2>
                <div class="deck-stats">
                    <?php foreach ($assigned_decks as $deck_item): ?>
                        <div class="deck-card">
                            <div class="deck-header">
                                <div class="deck-color" style="background-color: <?php echo htmlspecialchars($deck_item['color']); ?>"></div>
                                <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                            </div>
                            
                            <div class="deck-progress">
                                <div class="progress-item">
                                    <div class="progress-number"><?php echo $deck_item['total_words']; ?></div>
                                    <div class="progress-label" data-translate-key="total_words"><?php echo translate('total_words'); ?></div>
                                </div>
                                <div class="progress-item">
                                    <div class="progress-number"><?php echo $deck_item['words_to_review']; ?></div>
                                    <div class="progress-label" data-translate-key="to_review"><?php echo translate('to_review'); ?></div>
                                </div>
                                <div class="progress-item">
                                    <div class="progress-number"><?php echo $deck_item['learned_words_count'] ?: 0; ?></div>
                                    <div class="progress-label" data-translate-key="learned"><?php echo translate('learned'); ?></div>
                                </div>
                            </div>
                            
                            <div class="deck-progress-bar">
                                <div class="deck-progress-fill" 
                                     style="width: <?php echo $deck_item['total_words'] > 0 ? round(($deck_item['learned_words_count'] ?: 0) / $deck_item['total_words'] * 100, 1) : 0; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($recent_test_results)): ?>
            <div class="card">
                <h2 class="section-title" data-translate-key="recent_test_results"><?php echo translate('recent_test_results'); ?></h2>
                <div class="test-results">
                    <?php foreach ($recent_test_results as $result): ?>
                        <div class="test-result-item">
                            <div class="test-info">
                                <div class="test-name"><?php echo htmlspecialchars($result['test_name']); ?></div>
                                <div class="test-deck">
                                    <div class="deck-color" style="background-color: <?php echo htmlspecialchars($result['deck_color']); ?>"></div>
                                    <span data-translate-key="stats_test_deck_text" data-deck="<?php echo htmlspecialchars($result['deck_name']); ?>">
                                        <?php echo sprintf(translate('stats_test_deck_text'), htmlspecialchars($result['deck_name'])); ?>
                                    </span>
                                </div>
                                <div class="test-date"><?php echo date('d.m.Y H:i', strtotime($result['completed_at'])); ?></div>
                            </div>
                            <div class="test-score 
                                <?php 
                                $score = $result['score'];
                                if ($score >= 90) echo 'score-excellent';
                                elseif ($score >= 75) echo 'score-good';
                                elseif ($score >= 60) echo 'score-average';
                                else echo 'score-poor';
                                ?>">
                                <?php echo $result['score']; ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($daily_stats)): ?>
            <div class="card">
                <h2 class="section-title" data-translate-key="learning_activity"><?php echo translate('learning_activity'); ?></h2>
                <p style="color: #666; margin-bottom: 1rem;" data-translate-key="activity_last_30_days"><?php echo translate('activity_last_30_days'); ?></p>
                <div class="daily-activity">
                    <?php 
                    // Создаем массив с данными за последние 30 дней
                    $daily_data = [];
                    foreach ($daily_stats as $stat) {
                        $daily_data[$stat['review_date']] = $stat['reviews_count'];
                    }
                    
                    for ($i = 29; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $day = date('j', strtotime($date));
                        $count = isset($daily_data[$date]) ? $daily_data[$date] : 0;
                        $class = $count > 0 ? 'active' : '';
                        ?>
                        <div class="day-item <?php echo $class; ?>" title="<?php echo date('d.m.Y', strtotime($date)); ?>">
                            <div class="day-date"><?php echo $day; ?></div>
                            <?php if ($count > 0): ?>
                                <div class="day-count"><?php echo $count; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($assigned_decks) && empty($recent_test_results)): ?>
            <div class="card">
                <div class="empty-state">
                    <h3 data-translate-key="no_statistics_title"><?php echo translate('no_statistics_title'); ?></h3>
                    <p data-translate-key="no_statistics_desc"><?php echo translate('no_statistics_desc'); ?></p>
                    <a href="/student/flashcards" class="btn btn-primary" style="margin-top: 1rem;" data-translate-key="start_learning">
                        <?php echo translate('start_learning'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="/public/js/security.js"></script>
</body>
</html>
