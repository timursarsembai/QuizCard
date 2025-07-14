<?php
// Проверяем test_id
if (!isset($_GET['test_id'])) {
    header("Location: /student/tests");
    exit();
}

$test_id = $_GET['test_id'];

// Получаем результаты студента по этому тесту
$query = "SELECT ta.*, t.name as test_name, t.time_limit, d.name as deck_name, d.color as deck_color
          FROM test_attempts ta
          INNER JOIN tests t ON ta.test_id = t.id
          INNER JOIN decks d ON t.deck_id = d.id
          WHERE ta.test_id = :test_id AND ta.student_id = :student_id
          ORDER BY ta.completed_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':test_id', $test_id);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();

$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($attempts)) {
    header("Location: /student/tests");
    exit();
}

// Получаем конкретную попытку если указан attempt_id
$current_attempt = null;
if (isset($_GET['attempt_id'])) {
    $attempt_id = $_GET['attempt_id'];
    foreach ($attempts as $attempt) {
        if ($attempt['id'] == $attempt_id) {
            $current_attempt = $attempt;
            break;
        }
    }
}

// Если попытка не найдена, берем последнюю
if (!$current_attempt) {
    $current_attempt = $attempts[0];
    $attempt_id = $current_attempt['id'];
}

// Получаем детали ответов для текущей попытки
$query = "SELECT ta.*, tq.question, tq.option_a, tq.option_b, tq.option_c, tq.option_d, tq.correct_answer
          FROM test_answers ta
          INNER JOIN test_questions tq ON ta.question_id = tq.id
          WHERE ta.attempt_id = :attempt_id
          ORDER BY tq.id";
$stmt = $db->prepare($query);
$stmt->bindParam(':attempt_id', $attempt_id);
$stmt->execute();

$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Определяем цвет и текст для оценки
function getScoreClass($score) {
    if ($score >= 90) return ['class' => 'excellent', 'text' => translate('test_result_excellent')];
    if ($score >= 75) return ['class' => 'good', 'text' => translate('test_result_good')];
    if ($score >= 60) return ['class' => 'average', 'text' => translate('test_result_average')];
    return ['class' => 'poor', 'text' => translate('test_result_poor')];
}

$score_info = getScoreClass($current_attempt['score']);
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('test_result_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        .result-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .result-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #666;
            font-size: 0.9rem;
        }

        .score-indicator {
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .score-excellent { background: #d4edda; color: #155724; }
        .score-good { background: #cce7ff; color: #004085; }
        .score-average { background: #fff3cd; color: #856404; }
        .score-poor { background: #f8d7da; color: #721c24; }

        .attempts-selector {
            margin-bottom: 2rem;
        }

        .attempts-list {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .attempt-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }

        .attempt-btn.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .attempt-btn:hover {
            background: #f8f9fa;
        }

        .attempt-btn.active:hover {
            background: #0056b3;
        }

        .questions-review {
            margin-bottom: 2rem;
        }

        .question-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .question-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .question-number {
            font-weight: 600;
            color: #666;
        }

        .question-status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-correct {
            background: #d4edda;
            color: #155724;
        }

        .status-incorrect {
            background: #f8d7da;
            color: #721c24;
        }

        .question-content {
            padding: 1rem;
        }

        .question-text {
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .options-list {
            display: grid;
            gap: 0.5rem;
        }

        .option-item {
            padding: 0.8rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .option-letter {
            font-weight: 600;
            margin-right: 0.8rem;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .option-text {
            flex: 1;
        }

        .option-correct {
            background: #d4edda;
            border-color: #c3e6cb;
        }

        .option-correct .option-letter {
            background: #28a745;
            color: white;
        }

        .option-student {
            background: #f8d7da;
            border-color: #f5c6cb;
        }

        .option-student .option-letter {
            background: #dc3545;
            color: white;
        }

        .option-normal .option-letter {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }

        .deck-info {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .deck-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .deck-name {
            font-weight: 600;
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .result-summary {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .attempts-list {
                justify-content: center;
            }
            
            .action-buttons {
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
                <a href="/student/tests" data-translate-key="tests"><?php echo translate('tests'); ?></a>
                <a href="/student/flashcards" data-translate-key="flashcards"><?php echo translate('flashcards'); ?></a>
                <a href="/student/statistics" data-translate-key="statistics"><?php echo translate('statistics'); ?></a>
                <a href="/student/vocabulary-view" data-translate-key="vocabulary"><?php echo translate('vocabulary'); ?></a>
                <a href="/logout" data-translate-key="logout"><?php echo translate('logout'); ?></a>
            </nav>
            <?php include __DIR__ . '/language_switcher.php'; ?>
        </div>
    </div>

    <div class="container">
        <div class="result-header">
            <div class="container">
                <h1 data-translate-key="test_result_title"><?php echo translate('test_result_title'); ?></h1>
                <div class="deck-info">
                    <div class="deck-color" style="background-color: <?php echo htmlspecialchars($current_attempt['deck_color']); ?>"></div>
                    <div class="deck-name"><?php echo htmlspecialchars($current_attempt['deck_name']); ?></div>
                </div>
                <h2><?php echo htmlspecialchars($current_attempt['test_name']); ?></h2>
            </div>
        </div>

        <div class="result-summary">
            <div class="summary-card">
                <div class="summary-number" style="color: #007bff;"><?php echo $current_attempt['score']; ?>%</div>
                <div class="summary-label" data-translate-key="final_score"><?php echo translate('final_score'); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #28a745;"><?php echo $current_attempt['correct_answers']; ?></div>
                <div class="summary-label" data-translate-key="correct_answers"><?php echo translate('correct_answers'); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #6c757d;"><?php echo $current_attempt['total_questions']; ?></div>
                <div class="summary-label" data-translate-key="total_questions"><?php echo translate('total_questions'); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #17a2b8;">
                    <?php 
                    if ($current_attempt['time_spent']) {
                        $minutes = floor($current_attempt['time_spent'] / 60);
                        $seconds = $current_attempt['time_spent'] % 60;
                        echo sprintf('%d:%02d', $minutes, $seconds);
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="summary-label" data-translate-key="time_spent"><?php echo translate('time_spent'); ?></div>
            </div>
        </div>

        <div class="card">
            <div class="score-indicator score-<?php echo $score_info['class']; ?>">
                <?php echo $score_info['text']; ?>
            </div>
        </div>

        <?php if (count($attempts) > 1): ?>
            <div class="card attempts-selector">
                <h3 data-translate-key="select_attempt"><?php echo translate('select_attempt'); ?></h3>
                <div class="attempts-list">
                    <?php foreach ($attempts as $index => $attempt): ?>
                        <a href="/student/test-result?test_id=<?php echo $test_id; ?>&attempt_id=<?php echo $attempt['id']; ?>" 
                           class="attempt-btn <?php echo $attempt['id'] == $current_attempt['id'] ? 'active' : ''; ?>">
                            <span data-translate-key="attempt"><?php echo translate('attempt'); ?></span> <?php echo $index + 1; ?> 
                            (<?php echo $attempt['score']; ?>%)
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card questions-review">
            <h3 data-translate-key="detailed_review"><?php echo translate('detailed_review'); ?></h3>
            
            <?php foreach ($answers as $index => $answer): ?>
                <div class="question-item">
                    <div class="question-header">
                        <div class="question-number">
                            <span data-translate-key="question"><?php echo translate('question'); ?></span> <?php echo $index + 1; ?>
                        </div>
                        <div class="question-status <?php echo $answer['is_correct'] ? 'status-correct' : 'status-incorrect'; ?>">
                            <?php echo $answer['is_correct'] ? translate('correct') : translate('incorrect'); ?>
                        </div>
                    </div>
                    
                    <div class="question-content">
                        <div class="question-text"><?php echo htmlspecialchars($answer['question']); ?></div>
                        
                        <div class="options-list">
                            <?php 
                            $options = [
                                'A' => $answer['option_a'],
                                'B' => $answer['option_b'],
                                'C' => $answer['option_c'],
                                'D' => $answer['option_d']
                            ];
                            
                            foreach ($options as $letter => $text): 
                                $isCorrect = ($letter === $answer['correct_answer']);
                                $isStudentAnswer = ($letter === $answer['student_answer']);
                                
                                $class = 'option-normal';
                                if ($isCorrect) {
                                    $class = 'option-correct';
                                } elseif ($isStudentAnswer && !$isCorrect) {
                                    $class = 'option-student';
                                }
                            ?>
                                <div class="option-item <?php echo $class; ?>">
                                    <div class="option-letter"><?php echo $letter; ?></div>
                                    <div class="option-text"><?php echo htmlspecialchars($text); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="action-buttons">
            <a href="/student/test-take?test_id=<?php echo $test_id; ?>" class="btn btn-primary" data-translate-key="retake_test">
                <?php echo translate('retake_test'); ?>
            </a>
            <a href="/student/tests" class="btn btn-secondary" data-translate-key="back_to_tests">
                <?php echo translate('back_to_tests'); ?>
            </a>
            <a href="/student/statistics" class="btn btn-info" data-translate-key="view_statistics">
                <?php echo translate('view_statistics'); ?>
            </a>
        </div>
    </div>

    <script src="/public/js/security.js"></script>
</body>
</html>
