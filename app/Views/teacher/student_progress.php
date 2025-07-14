<?php
// Проверяем, что student_id принадлежит данному преподавателю
if (!isset($_GET['student_id'])) {
    header("Location: /teacher/students");
    exit();
}

$student_id = $_GET['student_id'];
$student_info = $user->getStudentInfo($student_id, $teacher_id);

if (!$student_info) {
    header("Location: /teacher/students");
    exit();
}

// Обработка сброса прогресса по колоде
if ($_POST && isset($_POST['reset_deck_progress'])) {
    $deck_id = $_POST['deck_id'];
    if ($vocabulary->resetDeckProgress($student_id, $deck_id, $teacher_id)) {
        $success = translate('deck_progress_reset_success');
    } else {
        $error = translate('deck_progress_reset_error');
    }
}

// Обработка полного сброса прогресса
if ($_POST && isset($_POST['reset_all_progress'])) {
    // Сбрасываем прогресс по словам
    $vocabulary_reset = $vocabulary->resetStudentProgress($student_id, $teacher_id);
    
    // Сбрасываем прогресс по тестам
    $tests_reset = $test->resetStudentTestProgress($student_id, $teacher_id);
    
    if ($vocabulary_reset && $tests_reset) {
        $success = translate('all_progress_reset_success');
    } else {
        $error = translate('all_progress_reset_error');
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
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('student_progress_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        .student-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .student-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-details h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .student-details p {
            opacity: 0.9;
        }

        .student-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            text-align: center;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .progress-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .vocabulary-progress {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .deck-progress-item {
            margin-bottom: 2rem;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }

        .deck-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .deck-info {
            display: flex;
            align-items: center;
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

        .deck-actions {
            display: flex;
            gap: 0.5rem;
        }

        .deck-content {
            padding: 1rem;
        }

        .progress-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .progress-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .progress-number {
            font-size: 1.3rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.3rem;
        }

        .progress-label {
            font-size: 0.8rem;
            color: #666;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            transition: width 0.5s ease;
        }

        .progress-text {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
        }

        .test-results {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .test-results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .test-results-table th,
        .test-results-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .test-results-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .score-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #cce7ff; color: #004085; }
        .score-average { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }

        .recent-attempts {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 2rem;
        }

        .attempt-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .attempt-info {
            flex: 1;
        }

        .attempt-test {
            font-weight: 600;
            margin-bottom: 0.3rem;
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

        .danger-actions {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .danger-title {
            color: #e53e3e;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .danger-warning {
            color: #744210;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .student-stats {
                grid-template-columns: 1fr;
            }
            
            .progress-overview {
                grid-template-columns: 1fr;
            }
            
            .progress-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .test-results-table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="container">
        <div class="student-header">
            <div class="student-info">
                <div class="student-details">
                    <h1><?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></h1>
                    <p>@<?php echo htmlspecialchars($student_info['username']); ?></p>
                </div>

                <div class="student-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo count($student_decks); ?></div>
                        <div class="stat-label" data-translate-key="assigned_decks"><?php echo translate('assigned_decks'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo array_sum(array_column($student_decks, 'total_words')); ?></div>
                        <div class="stat-label" data-translate-key="total_words"><?php echo translate('total_words'); ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $test_statistics['total_attempts'] ?: 0; ?></div>
                        <div class="stat-label" data-translate-key="test_attempts"><?php echo translate('test_attempts'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="progress-overview">
            <div class="vocabulary-progress">
                <h2 class="section-title" data-translate-key="vocabulary_progress"><?php echo translate('vocabulary_progress'); ?></h2>
                
                <?php if (empty($student_decks)): ?>
                    <div class="empty-state">
                        <p data-translate-key="no_assigned_decks"><?php echo translate('no_assigned_decks'); ?></p>
                        <a href="/teacher/deck-students?student_id=<?php echo $student_id; ?>" class="btn btn-primary">
                            <span data-translate-key="assign_decks"><?php echo translate('assign_decks'); ?></span>
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($student_decks as $deck_item): ?>
                        <div class="deck-progress-item">
                            <div class="deck-header">
                                <div class="deck-info">
                                    <div class="deck-color" style="background-color: <?php echo htmlspecialchars($deck_item['color']); ?>"></div>
                                    <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                                </div>
                                <div class="deck-actions">
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo translate('confirm_reset_deck_progress'); ?>')">
                                        <input type="hidden" name="deck_id" value="<?php echo $deck_item['id']; ?>">
                                        <button type="submit" name="reset_deck_progress" class="btn-danger" data-translate-key="reset_progress">
                                            <?php echo translate('reset_progress'); ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="deck-content">
                                <div class="progress-grid">
                                    <div class="progress-stat">
                                        <div class="progress-number"><?php echo $deck_item['total_words']; ?></div>
                                        <div class="progress-label" data-translate-key="total"><?php echo translate('total'); ?></div>
                                    </div>
                                    <div class="progress-stat">
                                        <div class="progress-number"><?php echo $deck_item['words_to_review']; ?></div>
                                        <div class="progress-label" data-translate-key="to_review"><?php echo translate('to_review'); ?></div>
                                    </div>
                                    <div class="progress-stat">
                                        <div class="progress-number"><?php echo $deck_item['learned_words_count'] ?: 0; ?></div>
                                        <div class="progress-label" data-translate-key="learned"><?php echo translate('learned'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="progress-bar">
                                    <div class="progress-fill" 
                                         style="width: <?php echo $deck_item['total_words'] > 0 ? round(($deck_item['learned_words_count'] ?: 0) / $deck_item['total_words'] * 100, 1) : 0; ?>%"></div>
                                </div>
                                <div class="progress-text">
                                    <?php echo $deck_item['total_words'] > 0 ? round(($deck_item['learned_words_count'] ?: 0) / $deck_item['total_words'] * 100, 1) : 0; ?>% 
                                    <span data-translate-key="completed"><?php echo translate('completed'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="test-results">
                <h2 class="section-title" data-translate-key="test_results"><?php echo translate('test_results'); ?></h2>
                
                <?php if (empty($all_test_results)): ?>
                    <div class="empty-state">
                        <p data-translate-key="no_test_results"><?php echo translate('no_test_results'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="test-results-table">
                        <thead>
                            <tr>
                                <th data-translate-key="test_name"><?php echo translate('test_name'); ?></th>
                                <th data-translate-key="deck"><?php echo translate('deck'); ?></th>
                                <th data-translate-key="attempts"><?php echo translate('attempts'); ?></th>
                                <th data-translate-key="best_score"><?php echo translate('best_score'); ?></th>
                                <th data-translate-key="last_attempt"><?php echo translate('last_attempt'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_test_results as $result): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($result['test']['name']); ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="deck-color" style="background-color: <?php echo htmlspecialchars($result['deck']['color']); ?>; width: 16px; height: 16px; margin-right: 8px;"></div>
                                            <?php echo htmlspecialchars($result['deck']['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo $result['stats']['attempts_count']; ?></td>
                                    <td>
                                        <?php 
                                        $score = $result['stats']['best_score'];
                                        $class = '';
                                        if ($score >= 90) $class = 'score-excellent';
                                        elseif ($score >= 75) $class = 'score-good';
                                        elseif ($score >= 60) $class = 'score-average';
                                        else $class = 'score-poor';
                                        ?>
                                        <span class="score-badge <?php echo $class; ?>"><?php echo $score; ?>%</span>
                                    </td>
                                    <td><?php echo date('d.m.Y', strtotime($result['stats']['last_attempt'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($recent_attempts)): ?>
            <div class="recent-attempts">
                <h2 class="section-title" data-translate-key="recent_test_attempts"><?php echo translate('recent_test_attempts'); ?></h2>
                
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
        <?php endif; ?>

        <div class="danger-actions">
            <div class="danger-title" data-translate-key="danger_zone"><?php echo translate('danger_zone'); ?></div>
            <div class="danger-warning" data-translate-key="progress_reset_warning"><?php echo translate('progress_reset_warning'); ?></div>
            
            <form method="POST" style="display: inline;" onsubmit="return confirm('<?php echo translate('confirm_reset_all_progress'); ?>')">
                <button type="submit" name="reset_all_progress" class="btn-danger" data-translate-key="reset_all_progress">
                    <?php echo translate('reset_all_progress'); ?>
                </button>
            </form>
        </div>

        <div style="margin-top: 2rem; text-align: center;">
            <a href="/teacher/students" class="btn btn-secondary" data-translate-key="back_to_students">
                <?php echo translate('back_to_students'); ?>
            </a>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="/public/js/security.js"></script>
</body>
</html>
