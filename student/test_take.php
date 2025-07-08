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

if (!$user->isLoggedIn() || $user->getRole() !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Проверяем test_id
if (!isset($_GET['test_id'])) {
    header("Location: tests.php");
    exit();
}

$test_id = $_GET['test_id'];
$current_test = $test->getTestById($test_id);

if (!$current_test) {
    header("Location: tests.php");
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
    header("Location: tests.php");
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
        header("Location: test_result.php?attempt_id=$attempt_id");
        exit();
    } else {
        $error = "Ошибка при сохранении результатов теста";
    }
}

// Получаем вопросы теста
$questions = $test->getTestQuestions($test_id);

if (empty($questions)) {
    header("Location: tests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Прохождение теста</title>
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

        .test-timer {
            background: rgba(255,255,255,0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .timer-warning {
            background: rgba(255,193,7,0.8) !important;
            color: #856404 !important;
        }

        .timer-danger {
            background: rgba(220,53,69,0.8) !important;
            color: white !important;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .test-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
            border-radius: 10px;
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

        .option.selected {
            border-color: #667eea;
            background: #e8f0ff;
        }

        .option input[type="radio"] {
            display: none;
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
            transition: all 0.3s;
        }

        .option.selected .option-letter {
            background: #5a6fd8;
            transform: scale(1.1);
        }

        .option-text {
            flex: 1;
            font-size: 1rem;
        }

        .progress-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-text {
            font-weight: 600;
            color: #333;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
        }

        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            transition: width 0.3s;
            border-radius: 10px;
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        .submit-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .test-header {
                padding: 1.5rem;
            }

            .test-title {
                font-size: 1.5rem;
            }

            .test-info {
                flex-direction: column;
                gap: 1rem;
            }

            .navigation {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🧪 <?php echo htmlspecialchars($current_test['name']); ?></h1>
            </div>
            <?php if ($current_test['time_limit']): ?>
                <div class="test-timer" id="timer">
                    ⏱️ <span id="timer-display"><?php echo $current_test['time_limit']; ?>:00</span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="test-header">
            <div class="test-title"><?php echo htmlspecialchars($current_test['name']); ?></div>
            <p>Колода: <?php echo htmlspecialchars($current_deck['name']); ?></p>
            
            <div class="test-info">
                <div class="info-item">
                    <div class="info-number"><?php echo count($questions); ?></div>
                    <div class="info-label">Вопросов</div>
                </div>
                <?php if ($current_test['time_limit']): ?>
                    <div class="info-item">
                        <div class="info-number"><?php echo $current_test['time_limit']; ?></div>
                        <div class="info-label">Минут</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <form id="testForm" method="POST" action="">
            <input type="hidden" name="submit_test" value="1">
            <input type="hidden" name="time_spent" id="timeSpent" value="0">

            <div class="progress-section">
                <div class="progress-header">
                    <span class="progress-text">Прогресс: <span id="current-question">1</span> из <?php echo count($questions); ?></span>
                    <span class="progress-text"><span id="answered-count">0</span> отвечено</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
            </div>

            <?php foreach ($questions as $index => $question): ?>
                <div class="question-card" id="question-<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                    <div class="question-number">Вопрос <?php echo $index + 1; ?></div>
                    <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                    
                    <div class="options">
                        <div class="option" onclick="selectOption(<?php echo $index; ?>, 'A')">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="A" id="q<?php echo $index; ?>_a">
                            <div class="option-letter">A</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_a']); ?></div>
                        </div>
                        
                        <div class="option" onclick="selectOption(<?php echo $index; ?>, 'B')">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="B" id="q<?php echo $index; ?>_b">
                            <div class="option-letter">B</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_b']); ?></div>
                        </div>
                        
                        <div class="option" onclick="selectOption(<?php echo $index; ?>, 'C')">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="C" id="q<?php echo $index; ?>_c">
                            <div class="option-letter">C</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_c']); ?></div>
                        </div>
                        
                        <div class="option" onclick="selectOption(<?php echo $index; ?>, 'D')">
                            <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="D" id="q<?php echo $index; ?>_d">
                            <div class="option-letter">D</div>
                            <div class="option-text"><?php echo htmlspecialchars($question['option_d']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="navigation">
                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeQuestion(-1)" disabled>
                    ← Предыдущий
                </button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeQuestion(1)">
                    Следующий →
                </button>
                <button type="button" class="btn btn-success" id="finishBtn" onclick="finishTest()" style="display: none;">
                    🏁 Завершить тест
                </button>
            </div>

            <div class="submit-section" id="submitSection" style="display: none;">
                <h3>🏁 Завершение теста</h3>
                <p>Вы ответили на <span id="final-answered">0</span> из <?php echo count($questions); ?> вопросов.</p>
                <div class="alert alert-warning">
                    <strong>Внимание!</strong> После отправки теста вы не сможете изменить свои ответы.
                </div>
                <button type="submit" class="btn btn-success">📝 Отправить тест</button>
                <button type="button" class="btn btn-secondary" onclick="hideSubmitSection()">❌ Продолжить тест</button>
            </div>
        </form>
    </div>

    <script>
        let currentQuestion = 0;
        const totalQuestions = <?php echo count($questions); ?>;
        const timeLimit = <?php echo $current_test['time_limit'] ?: 0; ?>;
        let startTime = Date.now();
        let timeRemaining = timeLimit * 60; // в секундах

        // Инициализация таймера
        if (timeLimit > 0) {
            updateTimer();
            const timerInterval = setInterval(() => {
                timeRemaining--;
                updateTimer();
                
                if (timeRemaining <= 0) {
                    clearInterval(timerInterval);
                    alert('Время теста истекло! Тест будет автоматически отправлен.');
                    document.getElementById('testForm').submit();
                }
            }, 1000);
        }

        function updateTimer() {
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            const timerElement = document.getElementById('timer-display');
            const timerContainer = document.getElementById('timer');
            
            if (timerElement) {
                timerElement.textContent = display;
                
                // Изменяем цвет в зависимости от оставшегося времени
                if (timeRemaining <= 60) { // Меньше минуты
                    timerContainer.className = 'test-timer timer-danger';
                } else if (timeRemaining <= 300) { // Меньше 5 минут
                    timerContainer.className = 'test-timer timer-warning';
                } else {
                    timerContainer.className = 'test-timer';
                }
            }
        }

        function selectOption(questionIndex, option) {
            // Убираем выделение со всех опций
            const questionCard = document.getElementById(`question-${questionIndex}`);
            const options = questionCard.querySelectorAll('.option');
            options.forEach(opt => opt.classList.remove('selected'));
            
            // Выделяем выбранную опцию
            const selectedOption = questionCard.querySelector(`#q${questionIndex}_${option.toLowerCase()}`);
            selectedOption.checked = true;
            selectedOption.closest('.option').classList.add('selected');
            
            updateProgress();
        }

        function changeQuestion(direction) {
            const current = document.getElementById(`question-${currentQuestion}`);
            current.style.display = 'none';
            
            currentQuestion += direction;
            
            const next = document.getElementById(`question-${currentQuestion}`);
            next.style.display = 'block';
            
            updateNavigation();
            updateQuestionCounter();
        }

        function updateNavigation() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const finishBtn = document.getElementById('finishBtn');
            
            prevBtn.disabled = currentQuestion === 0;
            
            if (currentQuestion === totalQuestions - 1) {
                nextBtn.style.display = 'none';
                finishBtn.style.display = 'inline-flex';
            } else {
                nextBtn.style.display = 'inline-flex';
                finishBtn.style.display = 'none';
            }
        }

        function updateQuestionCounter() {
            document.getElementById('current-question').textContent = currentQuestion + 1;
        }

        function updateProgress() {
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            const progress = (answered / totalQuestions) * 100;
            
            document.getElementById('progress-fill').style.width = `${progress}%`;
            document.getElementById('answered-count').textContent = answered;
            document.getElementById('final-answered').textContent = answered;
        }

        function finishTest() {
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            
            if (answered < totalQuestions) {
                if (!confirm(`Вы ответили только на ${answered} из ${totalQuestions} вопросов. Неотвеченные вопросы будут засчитаны как неправильные. Продолжить?`)) {
                    return;
                }
            }
            
            // Скрываем все вопросы и показываем секцию отправки
            document.querySelectorAll('.question-card').forEach(card => {
                card.style.display = 'none';
            });
            
            document.querySelector('.navigation').style.display = 'none';
            document.getElementById('submitSection').style.display = 'block';
        }

        function hideSubmitSection() {
            document.getElementById('submitSection').style.display = 'none';
            document.querySelector('.navigation').style.display = 'flex';
            document.getElementById(`question-${currentQuestion}`).style.display = 'block';
        }

        // Обновляем время, потраченное на тест, перед отправкой
        document.getElementById('testForm').addEventListener('submit', function() {
            const timeSpent = Math.floor((Date.now() - startTime) / 1000);
            document.getElementById('timeSpent').value = timeSpent;
        });

        // Предотвращаем случайную перезагрузку страницы
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
        });

        // Инициализация
        updateNavigation();
        updateProgress();
    </script>
</body>
</html>
