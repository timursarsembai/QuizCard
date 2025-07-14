<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Test.php';
require_once '../includes/init_language.php';
require_once '../includes/translations.php';

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
    if ($score >= 90) return ['class' => 'excellent', 'text' => translate('test_result_excellent')];
    if ($score >= 75) return ['class' => 'good', 'text' => translate('test_result_good')];
    if ($score >= 60) return ['class' => 'average', 'text' => translate('test_result_average')];
    return ['class' => 'poor', 'text' => translate('test_result_poor')];
}

$score_info = getScoreClass($attempt['score']);
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - <?php echo translate('test_result_title'); ?></title>
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
                <h1 data-translate-key="test_result_title">📊 <?php echo translate('test_result_title'); ?></h1>
                <div class="breadcrumb">
                    <a href="dashboard.php" data-translate-key="test_result_breadcrumb_home"><?php echo translate('test_result_breadcrumb_home'); ?></a> → 
                    <a href="tests.php" data-translate-key="test_result_breadcrumb_tests"><?php echo translate('test_result_breadcrumb_tests'); ?></a> → 
                    <span data-translate-key="test_result_breadcrumb_result"><?php echo translate('test_result_breadcrumb_result'); ?></span>
                </div>
            </div>
            <div class="nav-links">
                <?php include 'language_switcher.php'; ?>
                <a href="tests.php" class="btn" data-translate-key="test_result_to_tests"><?php echo translate('test_result_to_tests'); ?></a>
                <a href="../logout.php" class="btn" data-translate-key="logout_button"><?php echo translate('logout_button'); ?></a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="result-header">
            <div class="test-title"><?php echo htmlspecialchars($attempt['test_name']); ?></div>
            <div class="deck-name" data-translate-key="test_result_deck_label"><?php echo translate('test_result_deck_label'); ?> <?php echo htmlspecialchars($attempt['deck_name']); ?></div>
            
            <div class="score-display">
                <div class="score-circle score-<?php echo $score_info['class']; ?>">
                    <?php echo round($attempt['score']); ?>%
                </div>
                <div class="score-text score-<?php echo $score_info['class']; ?>" 
                     data-translate-key="test_result_<?php echo $score_info['class']; ?>" 
                     data-score-class="<?php echo $score_info['class']; ?>">
                    <?php echo $score_info['text']; ?>
                </div>
            </div>
            
            <div class="test-info">
                <div class="info-item">
                    <div class="info-number"><?php echo $attempt['correct_answers']; ?></div>
                    <div class="info-label" data-translate-key="test_result_correct_answers"><?php echo translate('test_result_correct_answers'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?php echo $attempt['total_questions'] - $attempt['correct_answers']; ?></div>
                    <div class="info-label" data-translate-key="test_result_errors"><?php echo translate('test_result_errors'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-number"><?php echo $attempt['total_questions']; ?></div>
                    <div class="info-label" data-translate-key="test_result_total_questions"><?php echo translate('test_result_total_questions'); ?></div>
                </div>
                <?php if ($attempt['time_spent']): ?>
                    <div class="info-item">
                        <div class="info-number"><?php echo gmdate("i:s", $attempt['time_spent']); ?></div>
                        <div class="info-label" data-translate-key="test_result_time_spent"><?php echo translate('test_result_time_spent'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="progress-summary">
            <div class="summary-card">
                <div class="summary-number" style="color: #28a745;"><?php echo $attempt['correct_answers']; ?></div>
                <div class="summary-label" data-translate-key="test_result_correct_label"><?php echo translate('test_result_correct_label'); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #dc3545;"><?php echo $attempt['total_questions'] - $attempt['correct_answers']; ?></div>
                <div class="summary-label" data-translate-key="test_result_incorrect_label"><?php echo translate('test_result_incorrect_label'); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-number" style="color: #667eea;"><?php echo round($attempt['score'], 1); ?>%</div>
                <div class="summary-label" data-translate-key="test_result_final_score"><?php echo translate('test_result_final_score'); ?></div>
            </div>
        </div>

        <div class="card">
            <h2 data-translate-key="test_result_detailed_review">📝 <?php echo translate('test_result_detailed_review'); ?></h2>
            <?php foreach ($answers as $index => $answer): ?>
                <div class="question-review <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                    <div class="question-text">
                        <span data-translate-key="test_result_question_number" data-number="<?php echo $index + 1; ?>"><?php echo str_replace('{number}', $index + 1, translate('test_result_question_number')); ?></span> <?php echo htmlspecialchars($answer['question']); ?>
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
                                    <div class="result-indicator result-correct" data-translate-key="test_result_your_answer_correct"><?php echo translate('test_result_your_answer_correct'); ?></div>
                                <?php elseif ($isSelected && !$isCorrect): ?>
                                    <div class="result-indicator result-incorrect" data-translate-key="test_result_your_answer_incorrect"><?php echo translate('test_result_your_answer_incorrect'); ?></div>
                                <?php elseif (!$isSelected && $isCorrect): ?>
                                    <div class="result-indicator result-correct" data-translate-key="test_result_correct_answer"><?php echo translate('test_result_correct_answer'); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="test_take.php?test_id=<?php echo $attempt['test_id']; ?>" class="btn btn-primary" data-translate-key="test_result_retake_test"><?php echo translate('test_result_retake_test'); ?></a>
            <a href="tests.php" class="btn btn-info" data-translate-key="test_result_all_tests"><?php echo translate('test_result_all_tests'); ?></a>
            <a href="dashboard.php" class="btn" data-translate-key="test_result_home"><?php echo translate('test_result_home'); ?></a>
        </div>
    </div>

    <script>
        // Глобальная функция для обновления переводов с плейсхолдерами
        window.updateTestResultTranslations = function(currentLang) {
            // Если язык не передан, получаем его из атрибута документа
            if (!currentLang) {
                currentLang = document.documentElement.lang || 'ru';
            }
            
            // Обновляем элементы с плейсхолдерами
            const questionElements = document.querySelectorAll('[data-translate-key="test_result_question_number"]');
            questionElements.forEach(element => {
                if (typeof translations !== 'undefined') {
                    const langTranslations = translations[currentLang] || translations['ru'];
                    if (langTranslations && langTranslations['test_result_question_number']) {
                        const questionNumber = element.getAttribute('data-number') || '1';
                        const translatedText = langTranslations['test_result_question_number']
                            .replace('{number}', questionNumber);
                        element.textContent = translatedText;
                    }
                }
            });
            
            // Обновляем элемент с оценкой (score-text) 
            const scoreTextElement = document.querySelector('.score-text[data-score-class]');
            if (scoreTextElement && typeof translations !== 'undefined') {
                const langTranslations = translations[currentLang] || translations['ru'];
                const scoreClass = scoreTextElement.getAttribute('data-score-class');
                const translateKey = 'test_result_' + scoreClass;
                
                if (langTranslations && langTranslations[translateKey]) {
                    scoreTextElement.textContent = langTranslations[translateKey];
                }
            }
        };

        // Переопределяем updateTranslations из language_switcher.php для обработки плейсхолдеров
        document.addEventListener('DOMContentLoaded', function() {
            // Сохраняем оригинальную функцию updateTranslations
            const originalUpdateTranslations = window.updateTranslations;
            
            // Переопределяем функцию updateTranslations
            window.updateTranslations = function() {
                // Вызываем оригинальную функцию
                if (originalUpdateTranslations) {
                    originalUpdateTranslations();
                }
                
                // Дополнительно обрабатываем элементы с плейсхолдерами
                // Получаем текущий язык из той же переменной, что использует language_switcher
                const lang = typeof currentLang !== 'undefined' ? currentLang : (document.documentElement.lang || 'ru');
                if (typeof window.updateTestResultTranslations === 'function') {
                    window.updateTestResultTranslations(lang);
                }
                
                // Обновляем title страницы
                const langTranslations = translations[lang] || translations['ru'];
                if (langTranslations && langTranslations['test_result_title']) {
                    document.title = 'QuizCard - ' + langTranslations['test_result_title'];
                }
            };
        });
    </script>
</body>
</html>
