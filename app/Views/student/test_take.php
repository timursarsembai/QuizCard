<?php
// Проверяем test_id
if (!isset($_GET['test_id'])) {
    header("Location: /student/tests");
    exit();
}

$test_id = $_GET['test_id'];
$current_test = $test->getTestById($test_id);

if (!$current_test) {
    header("Location: /student/tests");
    exit();
}

// Проверяем, что ученик имеет доступ к колоде этого теста
$student_decks = $deck->getDecksForStudent($student_id);
$has_access = false;
foreach ($student_decks as $student_deck) {
    if ($student_deck['id'] == $current_test['deck_id']) {
        $has_access = true;
        $current_deck = $student_deck;
        break;
    }
}

if (!$has_access) {
    header("Location: /student/tests");
    exit();
}

// Обработка отправки теста
if ($_POST && isset($_POST['submit_test'])) {
    $answers = $_POST['answers'] ?? [];
    $time_spent = intval($_POST['time_spent']) ?: 0;
    
    // Сохраняем попытку прохождения теста
    $attempt_id = $test->saveTestAttempt($test_id, $student_id, $answers, $time_spent);
    
    if ($attempt_id) {
        // Перенаправляем на страницу результатов
        header("Location: /student/test-result?attempt_id=$attempt_id");
        exit();
    } else {
        $error = translate('test_save_error');
    }
}

// Получаем вопросы теста
$questions = $test->getTestQuestions($test_id);

if (empty($questions)) {
    header("Location: /student/tests");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('test_taking_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .test-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .test-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .test-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
        }

        .info-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #007bff;
        }

        .timer-container {
            position: fixed;
            top: 100px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            min-width: 150px;
            text-align: center;
        }

        .timer-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .timer-display {
            font-size: 1.5rem;
            font-weight: 600;
            color: #007bff;
        }

        .timer-warning {
            color: #ffc107 !important;
        }

        .timer-danger {
            color: #dc3545 !important;
        }

        .progress-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
            height: 100%;
            transition: width 0.3s ease;
        }

        .question-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f8f9fa;
        }

        .question-number {
            background: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .question-counter {
            color: #666;
            font-size: 0.9rem;
        }

        .question-text {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            color: #333;
        }

        .options-container {
            display: grid;
            gap: 1rem;
        }

        .option-item {
            position: relative;
            cursor: pointer;
        }

        .option-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .option-label {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
        }

        .option-input:checked + .option-label {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.1);
        }

        .option-input:checked + .option-label .option-circle {
            background: #007bff;
            border-color: #007bff;
        }

        .option-input:checked + .option-label .option-circle::after {
            opacity: 1;
        }

        .option-circle {
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 50%;
            margin-right: 1rem;
            position: relative;
            transition: all 0.3s ease;
        }

        .option-circle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .option-letter {
            font-weight: 600;
            margin-right: 1rem;
            color: #666;
        }

        .option-text {
            flex: 1;
            line-height: 1.5;
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 1rem;
        }

        .nav-btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-prev {
            background: #6c757d;
            color: white;
        }

        .btn-prev:hover {
            background: #5a6268;
        }

        .btn-next {
            background: #007bff;
            color: white;
        }

        .btn-next:hover {
            background: #0056b3;
        }

        .btn-submit {
            background: #28a745;
            color: white;
            padding: 1rem 3rem;
            font-size: 1.1rem;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .questions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .question-mini {
            aspect-ratio: 1;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #666;
        }

        .question-mini.answered {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }

        .question-mini.current {
            background: #ffc107;
            border-color: #ffc107;
            color: #333;
        }

        .submit-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .submit-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #856404;
        }

        @media (max-width: 768px) {
            .timer-container {
                position: static;
                margin-bottom: 1rem;
            }
            
            .test-info {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .navigation-buttons {
                flex-direction: column;
            }
            
            .questions-grid {
                grid-template-columns: repeat(8, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-header">
            <h1 class="test-title"><?php echo htmlspecialchars($current_test['name']); ?></h1>
            <div class="deck-info">
                <div class="deck-color" style="background-color: <?php echo htmlspecialchars($current_deck['color']); ?>; width: 20px; height: 20px; border-radius: 50%; display: inline-block; margin-right: 8px;"></div>
                <span><?php echo htmlspecialchars($current_deck['name']); ?></span>
            </div>
            <div class="test-info">
                <div class="info-item">
                    <div class="info-icon"></div>
                    <span data-translate-key="questions_count"><?php echo translate('questions_count'); ?></span>: <?php echo count($questions); ?>
                </div>
                <?php if ($current_test['time_limit']): ?>
                <div class="info-item">
                    <div class="info-icon"></div>
                    <span data-translate-key="time_limit"><?php echo translate('time_limit'); ?></span>: <?php echo $current_test['time_limit']; ?> <span data-translate-key="minutes"><?php echo translate('minutes'); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($current_test['time_limit']): ?>
        <div class="timer-container">
            <div class="timer-label" data-translate-key="time_remaining"><?php echo translate('time_remaining'); ?></div>
            <div class="timer-display" id="timer"><?php echo $current_test['time_limit']; ?>:00</div>
        </div>
        <?php endif; ?>

        <form method="POST" id="testForm">
            <input type="hidden" name="time_spent" id="timeSpent" value="0">
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar" style="width: 0%"></div>
            </div>

            <div class="questions-grid">
                <?php for ($i = 0; $i < count($questions); $i++): ?>
                    <div class="question-mini" data-question="<?php echo $i; ?>" onclick="goToQuestion(<?php echo $i; ?>)">
                        <?php echo $i + 1; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <?php foreach ($questions as $index => $question): ?>
                <div class="question-container" id="question_<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                    <div class="question-header">
                        <div class="question-number">
                            <span data-translate-key="question"><?php echo translate('question'); ?></span> <?php echo $index + 1; ?>
                        </div>
                        <div class="question-counter">
                            <?php echo $index + 1; ?> <span data-translate-key="of"><?php echo translate('of'); ?></span> <?php echo count($questions); ?>
                        </div>
                    </div>

                    <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>

                    <div class="options-container">
                        <?php 
                        $options = [
                            'A' => $question['option_a'],
                            'B' => $question['option_b'],
                            'C' => $question['option_c'],
                            'D' => $question['option_d']
                        ];
                        
                        foreach ($options as $letter => $text): ?>
                            <div class="option-item">
                                <input type="radio" 
                                       class="option-input" 
                                       name="answers[<?php echo $question['id']; ?>]" 
                                       value="<?php echo $letter; ?>" 
                                       id="q<?php echo $question['id']; ?>_<?php echo $letter; ?>"
                                       onchange="updateProgress()">
                                <label class="option-label" for="q<?php echo $question['id']; ?>_<?php echo $letter; ?>">
                                    <div class="option-circle"></div>
                                    <div class="option-letter"><?php echo $letter; ?>.</div>
                                    <div class="option-text"><?php echo htmlspecialchars($text); ?></div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="navigation-buttons">
                        <button type="button" class="nav-btn btn-prev" 
                                onclick="prevQuestion()" 
                                id="prevBtn_<?php echo $index; ?>" 
                                style="<?php echo $index === 0 ? 'visibility: hidden;' : ''; ?>">
                            <span data-translate-key="previous"><?php echo translate('previous'); ?></span>
                        </button>
                        
                        <?php if ($index < count($questions) - 1): ?>
                            <button type="button" class="nav-btn btn-next" onclick="nextQuestion()">
                                <span data-translate-key="next"><?php echo translate('next'); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="submit-section" id="submitSection" style="display: none;">
                <h3 data-translate-key="ready_to_submit"><?php echo translate('ready_to_submit'); ?></h3>
                <div class="submit-warning">
                    <p data-translate-key="submit_warning"><?php echo translate('submit_warning'); ?></p>
                </div>
                <button type="submit" name="submit_test" class="nav-btn btn-submit" data-translate-key="submit_test">
                    <?php echo translate('submit_test'); ?>
                </button>
            </div>
        </form>
    </div>

    <script>
        let currentQuestion = 0;
        let totalQuestions = <?php echo count($questions); ?>;
        let timeLimit = <?php echo $current_test['time_limit'] ?: 0; ?>;
        let startTime = Date.now();
        let timer;

        function updateProgress() {
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            const progress = (answered / totalQuestions) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            // Обновляем мини-вопросы
            document.querySelectorAll('.question-mini').forEach((mini, index) => {
                const questionInputs = document.querySelectorAll(`input[name*="answers"][name*="${document.querySelectorAll('input[type="radio"]')[index * 4]?.name.match(/\[(\d+)\]/)?.[1] || ''}"]`);
                const isAnswered = Array.from(questionInputs).some(input => input.checked);
                
                mini.classList.toggle('answered', isAnswered);
                mini.classList.toggle('current', index === currentQuestion);
            });
        }

        function goToQuestion(index) {
            if (index >= 0 && index < totalQuestions) {
                document.getElementById(`question_${currentQuestion}`).style.display = 'none';
                currentQuestion = index;
                document.getElementById(`question_${currentQuestion}`).style.display = 'block';
                
                // Показываем секцию отправки только на последнем вопросе
                if (currentQuestion === totalQuestions - 1) {
                    document.getElementById('submitSection').style.display = 'block';
                } else {
                    document.getElementById('submitSection').style.display = 'none';
                }
                
                updateProgress();
            }
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                goToQuestion(currentQuestion + 1);
            }
        }

        function prevQuestion() {
            if (currentQuestion > 0) {
                goToQuestion(currentQuestion - 1);
            }
        }

        // Таймер
        if (timeLimit > 0) {
            let remainingTime = timeLimit * 60;
            
            timer = setInterval(function() {
                remainingTime--;
                
                const minutes = Math.floor(remainingTime / 60);
                const seconds = remainingTime % 60;
                const timerDisplay = document.getElementById('timer');
                
                timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                // Предупреждения о времени
                if (remainingTime <= 300) { // 5 минут
                    timerDisplay.classList.add('timer-warning');
                }
                if (remainingTime <= 60) { // 1 минута
                    timerDisplay.classList.remove('timer-warning');
                    timerDisplay.classList.add('timer-danger');
                }
                
                if (remainingTime <= 0) {
                    clearInterval(timer);
                    document.getElementById('testForm').submit();
                }
            }, 1000);
        }

        // Отслеживание времени
        document.getElementById('testForm').addEventListener('submit', function() {
            const timeSpent = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('timeSpent').value = timeSpent;
        });

        // Предупреждение о закрытии страницы
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });

        // Инициализация
        updateProgress();
    </script>
</body>
</html>
