<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Test.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$test = new Test($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Проверяем attempt_id
if (!isset($_GET['attempt_id'])) {
    header("Location: tests.php");
    exit();
}

$attempt_id = $_GET['attempt_id'];

// Получаем информацию о попытке
$query = "SELECT ta.*, t.name as test_name, t.time_limit, d.name as deck_name, d.color as deck_color
          FROM test_attempts ta
          INNER JOIN tests t ON ta.test_id = t.id
          INNER JOIN decks d ON t.deck_id = d.id
          WHERE ta.id = :attempt_id AND ta.student_id = :student_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':attempt_id', $attempt_id);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();

$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    header("Location: tests.php");
    exit();
}

// Получаем детали ответов
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
    if ($score >= 90) return ['class' => 'excellent', 'text' => 'Отлично!'];
    if ($score >= 75) return ['class' => 'good', 'text' => 'Хорошо!'];
    if ($score >= 60) return ['class' => 'average', 'text' => 'Удовлетворительно'];
    return ['class' => 'poor', 'text' => 'Нужно подучить'];
}

$score_info = getScoreClass($attempt['score']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Результат теста</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            color: #333;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .result-header {
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .result-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: <?php echo htmlspecialchars($attempt['deck_color']); ?>;
        }

        .test-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .deck-name {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .score-display {
            margin: 2rem 0;
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            color: white;
            position: relative;
        }

        .score-excellent {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .score-good {
            background: linear-gradient(135deg, #17a2b8, #20c997);
        }

        .score-average {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }

        .score-poor {
            background: linear-gradient(135deg, #dc3545, #e83e8c);
        }

        .score-text {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .test-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .info-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .info-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .question-review {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #e9ecef;
        }

        .question-review.correct {
            border-left-color: #28a745;
            background: #d4edda;
        }

        .question-review.incorrect {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        .question-text {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #333;
        }

        .answer-options {
            display: grid;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .answer-option {
            padding: 0.75rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .option-letter {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #6c757d;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .answer-option.selected {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }

        .answer-option.correct {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .answer-option.correct .option-letter {
            background: #28a745;
        }

        .answer-option.incorrect {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .answer-option.incorrect .option-letter {
            background: #dc3545;
        }

        .result-indicator {
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.9rem;
        }

        .result-correct {
            background: #28a745;
            color: white;
        }

        .result-incorrect {
            background: #dc3545;
            color: white;
        }

        .actions {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .actions .btn {
            margin: 0 0.5rem;
        }

        .progress-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .summary-label {
            color: #666;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .result-header {
                padding: 2rem 1rem;
            }

            .test-title {
                font-size: 1.5rem;
            }

            .score-circle {
                width: 120px;
                height: 120px;
                font-size: 2.5rem;
            }

            .test-info {
                grid-template-columns: repeat(2, 1fr);
            }

            .actions .btn {
                display: block;
                margin: 0.5rem 0;
                width: 100%;
            }

            .progress-summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>📊 Результат теста</h1>
                <div class="breadcrumb">
                    <a href="dashboard.php">Главная</a> → 
                    <a href="tests.php">Тесты</a> → 
                    Результат
                </div>
            </div>
            <div class="nav-links">
                <a href="tests.php" class="btn">← К тестам</a>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="result-header">
            <div class="test-title"><?php echo htmlspecialchars($attempt['test_name']); ?></div>
            <div class="deck-name">Колода: <?php echo htmlspecialchars($attempt['deck_name']); ?></div>
            
            <div class="score-display">
                <div class="score-circle score-<?php echo $score_info['class']; ?>">
                    <?php echo round($attempt['score']); ?>%
                </div>
                <div class="score-text score-<?php echo $score_info['class']; ?>">
                    <?php echo $score_info['text']; ?>
                </div>
            </div>
            
            <div class="test-info">
                <div class="info-item">
                    <div class="info-number"><?php echo $attempt['correct_answers']; ?></div>
                    <div class="info-label">Правильных ответов</div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?php echo $attempt['total_questions'] - $attempt['correct_answers']; ?></div>
                    <div class="info-label">Ошибок</div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?php echo $attempt['total_questions']; ?></div>
                    <div class="info-label">Всего вопросов</div>
                </div>
                <?php if ($attempt['time_spent']): ?>
                    <div class="info-item">
                        <div class="info-number"><?php echo gmdate("i:s", $attempt['time_spent']); ?></div>
                        <div class="info-label">Времени потрачено</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="progress-summary">
            <div class="summary-card">
                <div class="summary-number" style="color: #28a745;"><?php echo $attempt['correct_answers']; ?></div>
                <div class="summary-label">Правильно</div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #dc3545;"><?php echo $attempt['total_questions'] - $attempt['correct_answers']; ?></div>
                <div class="summary-label">Неправильно</div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #667eea;"><?php echo round($attempt['score'], 1); ?>%</div>
                <div class="summary-label">Итоговый балл</div>
            </div>
        </div>

        <div class="card">
            <h2>📝 Подробный разбор ответов</h2>
            <?php foreach ($answers as $index => $answer): ?>
                <div class="question-review <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                    <div class="question-text">
                        Вопрос <?php echo $index + 1; ?>: <?php echo htmlspecialchars($answer['question']); ?>
                    </div>
                    
                    <div class="answer-options">
                        <?php 
                        $options = ['A' => $answer['option_a'], 'B' => $answer['option_b'], 'C' => $answer['option_c'], 'D' => $answer['option_d']];
                        foreach ($options as $letter => $text): 
                            $isSelected = ($answer['selected_answer'] === $letter);
                            $isCorrect = ($answer['correct_answer'] === $letter);
                            
                            $classes = ['answer-option'];
                            if ($isSelected && $isCorrect) {
                                $classes[] = 'correct';
                            } elseif ($isSelected && !$isCorrect) {
                                $classes[] = 'incorrect';
                            } elseif (!$isSelected && $isCorrect) {
                                $classes[] = 'correct';
                            } elseif ($isSelected) {
                                $classes[] = 'selected';
                            }
                        ?>
                            <div class="<?php echo implode(' ', $classes); ?>">
                                <div class="option-letter"><?php echo $letter; ?></div>
                                <div class="option-text"><?php echo htmlspecialchars($text); ?></div>
                                <?php if ($isSelected && $isCorrect): ?>
                                    <div class="result-indicator result-correct">✓ Ваш ответ - правильный</div>
                                <?php elseif ($isSelected && !$isCorrect): ?>
                                    <div class="result-indicator result-incorrect">✗ Ваш ответ - неправильный</div>
                                <?php elseif (!$isSelected && $isCorrect): ?>
                                    <div class="result-indicator result-correct">✓ Правильный ответ</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="test_take.php?test_id=<?php echo $attempt['test_id']; ?>" class="btn btn-primary">🔄 Пройти тест заново</a>
            <a href="tests.php" class="btn btn-info">📚 Все тесты</a>
            <a href="dashboard.php" class="btn">🏠 На главную</a>
        </div>
    </div>
</body>
</html>
