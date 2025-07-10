<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../classes/Test.php';
require_once '../includes/init_language.php';
require_once '../includes/translations.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);
$test = new Test($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Получаем колоды ученика
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
    <title><?php echo translate('student_tests_title'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
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
            font-size: 1.8rem;
            font-weight: 300;
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

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            display: inline-block;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: rgba(255,255,255,0.9);
            color: #667eea;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .welcome-card h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .deck-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e9ecef;
        }

        .deck-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .deck-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .deck-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .deck-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }

        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .test-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }

        .test-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .test-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
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
            background: white;
            border-radius: 8px;
        }

        .info-number {
            font-size: 1.1rem;
            font-weight: bold;
            color: #667eea;
        }

        .info-label {
            font-size: 0.8rem;
            color: #666;
        }

        .test-stats {
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
        }

        .best-score {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .score-excellent { color: #28a745; }
        .score-good { color: #17a2b8; }
        .score-average { color: #ffc107; }
        .score-poor { color: #dc3545; }
        .score-none { color: #6c757d; }

        .test-actions {
            display: flex;
            gap: 0.5rem;
        }

        .test-actions .btn {
            flex: 1;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            text-align: center;
            border-radius: 8px;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .recent-attempts {
            margin-top: 1rem;
        }

        .attempt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .attempt-info {
            flex: 1;
        }

        .attempt-test {
            font-weight: 600;
            color: #333;
        }

        .attempt-date {
            font-size: 0.9rem;
            color: #666;
        }

        .attempt-score {
            font-size: 1.1rem;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .welcome-card {
                padding: 2rem 1rem;
            }

            .welcome-card h2 {
                font-size: 2rem;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .tests-grid {
                grid-template-columns: 1fr;
            }

            .test-info {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }

            .test-actions {
                flex-direction: column;
            }

            .deck-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="tests_page_title"><?php echo translate('tests_page_title'); ?></h1>
                <div class="breadcrumb">
                    <a href="dashboard.php" data-translate-key="nav_dashboard"><?php echo translate('nav_dashboard'); ?></a> → <span data-translate-key="tests"><?php echo translate('tests'); ?></span>
                </div>
            </div>
            <div class="user-info">
                <?php include 'language_switcher.php'; ?>
                <a href="dashboard.php" class="btn" data-translate-key="go_to_main"><?php echo translate('go_to_main'); ?></a>
                <a href="../logout.php" class="btn" data-translate-key="logout"><?php echo translate('logout'); ?></a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <h2 data-translate-key="check_knowledge_title"><?php echo translate('check_knowledge_title'); ?></h2>
            <p data-translate-key="check_knowledge_desc"><?php echo translate('check_knowledge_desc'); ?></p>
        </div>

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
                    <a href="flashcards.php" class="btn btn-primary" style="margin-top: 1rem;" data-translate-key="study_flashcards"><?php echo translate('study_flashcards'); ?></a>
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
                                        <a href="test_take.php?test_id=<?php echo $test_item['id']; ?>" class="btn btn-success" data-translate-key="take_test">
                                            <?php echo translate('take_test'); ?>
                                        </a>
                                        <?php if (isset($test_item['student_stats']) && is_array($test_item['student_stats']) && ($test_item['student_stats']['attempts_count'] ?? 0) > 0): ?>
                                            <a href="test_result.php?test_id=<?php echo $test_item['id']; ?>" class="btn btn-info" data-translate-key="view_results">
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
</body>
</html>
