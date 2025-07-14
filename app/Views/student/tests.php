<?php
// Получаем студенческие колоды
$student_decks = $deck->getDecksForStudent($student_id);

// Получаем тесты для каждой колоды
$available_tests = [];
foreach ($student_decks as $deck_item) {
    $deck_tests = $test->getTestsByDeck($deck_item['id']);
    if (!empty($deck_tests)) {
        for ($i = 0; $i < count($deck_tests); $i++) {
            // Получаем статистику ученика по этому тесту
            $deck_tests[$i]['student_stats'] = $test->getStudentTestStats($deck_tests[$i]['id'], $student_id);
        }
        $available_tests[$deck_item['id']] = [
            'deck' => $deck_item,
            'tests' => $deck_tests
        ];
    }
}

// Получаем последние результаты ученика
$recent_attempts = $test->getStudentRecentAttempts($student_id, 5);
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('tests_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .deck-section {
            margin-bottom: 2rem;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }

        .deck-header {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .deck-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .deck-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .test-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .test-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .test-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .test-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .info-item {
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .info-number {
            font-weight: 600;
            color: #007bff;
            font-size: 1.1rem;
        }

        .info-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.2rem;
        }

        .test-stats {
            margin-bottom: 1rem;
        }

        .best-score {
            padding: 0.5rem;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #cce7ff; color: #004085; }
        .score-average { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }
        .score-none { background: #e2e3e5; color: #6c757d; }

        .test-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .test-actions .btn {
            flex: 1;
            min-width: 100px;
        }

        .recent-attempts {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .attempt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .attempt-item:last-child {
            border-bottom: none;
        }

        .attempt-info {
            flex: 1;
        }

        .attempt-test {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .attempt-date {
            font-size: 0.9rem;
            color: #666;
        }

        .attempt-score {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            min-width: 60px;
            text-align: center;
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
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .tests-grid {
                grid-template-columns: 1fr;
            }
            
            .test-info {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .test-actions {
                flex-direction: column;
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
                <a href="/student/tests" class="active" data-translate-key="tests"><?php echo translate('tests'); ?></a>
                <a href="/student/flashcards" data-translate-key="flashcards"><?php echo translate('flashcards'); ?></a>
                <a href="/student/statistics" data-translate-key="statistics"><?php echo translate('statistics'); ?></a>
                <a href="/student/vocabulary-view" data-translate-key="vocabulary"><?php echo translate('vocabulary'); ?></a>
                <a href="/logout" data-translate-key="logout"><?php echo translate('logout'); ?></a>
            </nav>
            <?php include __DIR__ . '/language_switcher.php'; ?>
        </div>
    </div>

    <div class="container">
        <header class="page-header">
            <h1 data-translate-key="tests_title"><?php echo translate('tests_title'); ?></h1>
            <p data-translate-key="tests_subtitle"><?php echo translate('tests_subtitle'); ?></p>
        </header>

        <?php
        // Подсчитываем общую статистику
        $total_tests = 0;
        $total_attempts = 0;
        $total_score = 0;
        $tests_with_attempts = 0;

        foreach ($available_tests as $deck_data) {
            $total_tests += count($deck_data['tests']);
            foreach ($deck_data['tests'] as $test_item) {
                if (isset($test_item['student_stats']) && is_array($test_item['student_stats'])) {
                    $total_attempts += $test_item['student_stats']['attempts_count'] ?? 0;
                    if (isset($test_item['student_stats']['best_score']) && $test_item['student_stats']['best_score'] !== null) {
                        $total_score += $test_item['student_stats']['best_score'];
                        $tests_with_attempts++;
                    }
                }
            }
        }

        $average_score = $tests_with_attempts > 0 ? round($total_score / $tests_with_attempts, 1) : 0;
        ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_tests; ?></div>
                <div class="stat-label" data-translate-key="available_tests_stat"><?php echo translate('available_tests_stat'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_attempts; ?></div>
                <div class="stat-label" data-translate-key="attempts_completed_stat"><?php echo translate('attempts_completed_stat'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $average_score; ?>%</div>
                <div class="stat-label" data-translate-key="average_score_stat"><?php echo translate('average_score_stat'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($available_tests); ?></div>
                <div class="stat-label" data-translate-key="decks_with_tests_stat"><?php echo translate('decks_with_tests_stat'); ?></div>
            </div>
        </div>

        <div class="card">
            <h2 data-translate-key="tests_by_decks_title"><?php echo translate('tests_by_decks_title'); ?></h2>
            <?php if (empty($available_tests)): ?>
                <div class="empty-state">
                    <h3 data-translate-key="tests_not_found_title"><?php echo translate('tests_not_found_title'); ?></h3>
                    <p data-translate-key="tests_not_found_desc"><?php echo translate('tests_not_found_desc'); ?></p>
                    <a href="/student/flashcards" class="btn btn-primary" style="margin-top: 1rem;" data-translate-key="study_flashcards"><?php echo translate('study_flashcards'); ?></a>
                </div>
            <?php else: ?>
                <?php foreach ($available_tests as $deck_data): ?>
                    <div class="deck-section">
                        <div class="deck-header">
                            <div class="deck-color" style="background-color: <?php echo htmlspecialchars($deck_data['deck']['color']); ?>"></div>
                            <div class="deck-name"><?php echo htmlspecialchars($deck_data['deck']['name']); ?></div>
                        </div>
                        
                        <div class="tests-grid">
                            <?php foreach ($deck_data['tests'] as $test_item): ?>
                                <div class="test-card">
                                    <div class="test-name"><?php echo htmlspecialchars($test_item['name']); ?></div>
                                    
                                    <div class="test-info">
                                        <div class="info-item">
                                            <div class="info-number"><?php echo $test_item['questions_count']; ?></div>
                                            <div class="info-label" data-translate-key="questions_count"><?php echo translate('questions_count'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-number"><?php echo $test_item['time_limit'] ?: '∞'; ?></div>
                                            <div class="info-label" data-translate-key="minutes_count"><?php echo translate('minutes_count'); ?></div>
                                        </div>
                                        <div class="info-item">
                                            <div class="info-number"><?php echo $test_item['student_stats']['attempts_count'] ?? 0; ?></div>
                                            <div class="info-label" data-translate-key="attempts_count"><?php echo translate('attempts_count'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($test_item['student_stats']) && is_array($test_item['student_stats']) && $test_item['student_stats']['best_score'] !== null): ?>
                                        <div class="test-stats">
                                            <div class="best-score 
                                                <?php 
                                                $score = $test_item['student_stats']['best_score'];
                                                if ($score >= 90) echo 'score-excellent';
                                                elseif ($score >= 75) echo 'score-good';
                                                elseif ($score >= 60) echo 'score-average';
                                                else echo 'score-poor';
                                                ?>">
                                                <span data-translate-key="best_result"><?php echo translate('best_result'); ?></span>: <?php echo $test_item['student_stats']['best_score']; ?>%
                                            </div>
                                            <?php if ($test_item['student_stats']['last_attempt']): ?>
                                            <div style="font-size: 0.9rem; color: #666;">
                                                <span data-translate-key="last_attempt"><?php echo translate('last_attempt'); ?></span>: <?php echo date('d.m.Y', strtotime($test_item['student_stats']['last_attempt'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="test-stats">
                                            <div class="best-score score-none" data-translate-key="test_not_taken"><?php echo translate('test_not_taken'); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="test-actions">
                                        <a href="/student/test-take?test_id=<?php echo $test_item['id']; ?>" class="btn btn-success" data-translate-key="take_test">
                                            <?php echo translate('take_test'); ?>
                                        </a>
                                        <?php if (isset($test_item['student_stats']) && is_array($test_item['student_stats']) && ($test_item['student_stats']['attempts_count'] ?? 0) > 0): ?>
                                            <a href="/student/test-result?test_id=<?php echo $test_item['id']; ?>" class="btn btn-info" data-translate-key="view_results">
                                                <?php echo translate('view_results'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($recent_attempts)): ?>
            <div class="card">
                <h2 data-translate-key="recent_results_title"><?php echo translate('recent_results_title'); ?></h2>
                <div class="recent-attempts">
                    <?php foreach ($recent_attempts as $attempt): ?>
                        <div class="attempt-item">
                            <div class="attempt-info">
                                <div class="attempt-test"><?php echo htmlspecialchars($attempt['test_name']); ?></div>
                                <div class="attempt-date"><?php echo date('d.m.Y H:i', strtotime($attempt['completed_at'])); ?></div>
                            </div>
                            <div class="attempt-score 
                                <?php 
                                $score = $attempt['score'];
                                if ($score >= 90) echo 'score-excellent';
                                elseif ($score >= 75) echo 'score-good';
                                elseif ($score >= 60) echo 'score-average';
                                else echo 'score-poor';
                                ?>">
                                <?php echo $attempt['score']; ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="/public/js/security.js"></script>
</body>
</html>
