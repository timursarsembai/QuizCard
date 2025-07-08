<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../classes/Test.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);
$test = new Test($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Проверяем test_id
if (!isset($_GET['test_id'])) {
    header("Location: decks.php");
    exit();
}

$test_id = $_GET['test_id'];
$current_test = $test->getTestById($test_id);

if (!$current_test) {
    header("Location: decks.php");
    exit();
}

// Проверяем, что тест принадлежит преподавателю
$current_deck = $deck->getDeckById($current_test['deck_id'], $teacher_id);
if (!$current_deck) {
    header("Location: decks.php");
    exit();
}

// Получаем вопросы теста
$questions = $test->getTestQuestions($test_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Предварительный просмотр теста</title>
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

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .test-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .test-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }

        .test-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            color: #666;
        }

        .info-item {
            text-align: center;
        }

        .info-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .info-label {
            font-size: 0.9rem;
        }

        .question-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }

        .question-number {
            color: #667eea;
            font-weight: bold;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .question-text {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .options {
            display: grid;
            gap: 0.75rem;
        }

        .option {
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .option:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .option.correct {
            border-color: #28a745;
            background: #d4edda;
            color: #155724;
        }

        .option-letter {
            background: #667eea;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
        }

        .option.correct .option-letter {
            background: #28a745;
        }

        .option-text {
            flex: 1;
        }

        .correct-indicator {
            color: #28a745;
            font-weight: bold;
        }

        .preview-notice {
            background: #e8f4fd;
            border-left: 4px solid #17a2b8;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 5px;
        }

        .preview-notice h3 {
            color: #17a2b8;
            margin-bottom: 0.5rem;
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

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .test-info {
                flex-direction: column;
                gap: 1rem;
            }

            .actions .btn {
                display: block;
                margin: 0.5rem 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>👁️ Предварительный просмотр</h1>
                <div class="breadcrumb">
                    <a href="decks.php">Колоды</a> → 
                    <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>">Тесты</a> → 
                    Просмотр
                </div>
            </div>
            <div class="nav-links">
                <a href="test_edit.php?test_id=<?php echo $test_id; ?>" class="btn btn-primary">✏️ Редактировать</a>
                <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" class="btn">← Назад</a>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="preview-notice">
            <h3>ℹ️ Режим предварительного просмотра</h3>
            <p>Здесь показано, как тест будет выглядеть для учеников. Правильные ответы выделены зеленым цветом.</p>
        </div>

        <div class="test-header">
            <div class="test-title"><?php echo htmlspecialchars($current_test['name']); ?></div>
            <p>Колода: <?php echo htmlspecialchars($current_deck['name']); ?></p>
            
            <div class="test-info">
                <div class="info-item">
                    <div class="info-number"><?php echo count($questions); ?></div>
                    <div class="info-label">Вопросов</div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?php echo $current_test['time_limit'] ?: '∞'; ?></div>
                    <div class="info-label">Минут</div>
                </div>
            </div>
        </div>

        <?php if (empty($questions)): ?>
            <div class="question-card">
                <p style="text-align: center; color: #666; font-style: italic;">
                    В тесте пока нет вопросов. 
                    <a href="test_edit.php?test_id=<?php echo $test_id; ?>">Добавьте вопросы</a> 
                    для предварительного просмотра.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <div class="question-number">Вопрос <?php echo $index + 1; ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                    
                    <div class="options">
                        <div class="option <?php echo $question['correct_answer'] === 'A' ? 'correct' : ''; ?>">
                            <div class="option-letter">A</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_a']); ?></div>
                            <?php if ($question['correct_answer'] === 'A'): ?>
                                <div class="correct-indicator">✓ Правильный ответ</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="option <?php echo $question['correct_answer'] === 'B' ? 'correct' : ''; ?>">
                            <div class="option-letter">B</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_b']); ?></div>
                            <?php if ($question['correct_answer'] === 'B'): ?>
                                <div class="correct-indicator">✓ Правильный ответ</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="option <?php echo $question['correct_answer'] === 'C' ? 'correct' : ''; ?>">
                            <div class="option-letter">C</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_c']); ?></div>
                            <?php if ($question['correct_answer'] === 'C'): ?>
                                <div class="correct-indicator">✓ Правильный ответ</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="option <?php echo $question['correct_answer'] === 'D' ? 'correct' : ''; ?>">
                            <div class="option-letter">D</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_d']); ?></div>
                            <?php if ($question['correct_answer'] === 'D'): ?>
                                <div class="correct-indicator">✓ Правильный ответ</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="actions">
            <a href="test_edit.php?test_id=<?php echo $test_id; ?>" class="btn btn-primary">✏️ Редактировать тест</a>
            <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" class="btn">← Вернуться к тестам</a>
        </div>
    </div>
</body>
</html>
