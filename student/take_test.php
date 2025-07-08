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

// Проверяем, что студент имеет доступ к этому тесту (через назначенную колоду)
$access_check = $test->checkStudentTestAccess($test_id, $student_id);
if (!$access_check) {
    header("Location: tests.php");
    exit();
}

// Обработка отправки теста
if ($_POST && isset($_POST['submit_test'])) {
    $answers = $_POST['answers'] ?? [];
    $start_time = $_POST['start_time'] ?? null;
    $time_spent = $start_time ? (time() - $start_time) : null;
    
    // Сохраняем попытку
    $attempt_id = $test->submitTestAttempt($test_id, $student_id, $answers, $time_spent);
    
    if ($attempt_id) {
        // Перенаправляем на страницу результатов
        header("Location: test_result.php?attempt_id=$attempt_id");
        exit();
    } else {
        $error = "Ошибка при сохранении теста";
    }
}

// Получаем вопросы теста
$questions = $test->getTestQuestions($test_id);

if (empty($questions)) {
    $error = "В тесте нет вопросов";
}

// Перемешиваем вопросы для каждого ученика
if (!empty($questions)) {
    // Используем student_id как seed для стабильного перемешивания
    mt_srand($student_id + $test_id);
    shuffle($questions);
    mt_srand(); // Сбрасываем seed
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
            background: #f5f5f5;
            min-height: 100vh;
            color: #333;
        }

        .header {
            background: #667eea;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
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

        .test-info {
            text-align: center;
        }

        .timer {
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .timer.warning {
            background: #ffc107;
            color: #333;
        }

        .timer.danger {
            background: #dc3545;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .test-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .test-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1rem;
        }

        .test-meta {
            display: flex;
            justify-content: center;
            gap: 2rem;
            color: #666;
            margin-top: 1rem;
        }

        .meta-item {
            text-align: center;
        }

        .meta-number {
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
        }

        .question-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }

        .question-counter {
            color: #666;
            font-size: 0.9rem;
        }

        .question-text {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .options {
            display: grid;
            gap: 1rem;
        }

        .option {
            position: relative;
        }

        .option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .option-label {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .option input[type="radio"]:checked + .option-label {
            border-color: #667eea;
            background: #f0f4ff;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .option-label:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }

        .option-letter {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            flex-shrink: 0;
            transition: all 0.3s;
        }

        .option input[type="radio"]:checked + .option-label .option-letter {
            background: #4c63d2;
            transform: scale(1.1);
        }

        .option-text {
            flex: 1;
            font-size: 1.1rem;
        }

        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 8px;
            margin: 2rem 0;
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
            align-items: center;
            margin: 2rem 0;
            padding: 1.5rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: sticky;
            bottom: 2rem;
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #4c63d2;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .progress-info {
            text-align: center;
            color: #666;
            margin-bottom: 1rem;
        }

        .unanswered-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            display: none;
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

            .test-meta {
                flex-direction: column;
                gap: 1rem;
            }

            .question-card {
                padding: 1.5rem;
            }

            .question-text {
                font-size: 1.1rem;
            }

            .option-label {
                padding: 1rem;
            }

            .navigation {
                flex-direction: column;
                gap: 1rem;
                bottom: 1rem;
            }

            .btn {
                width: 100%;
                text-align: center;
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
            <div class="test-info">
                <?php if ($current_test['time_limit']): ?>
                    <div class="timer" id="timer">
                        ⏰ <?php echo $current_test['time_limit']; ?>:00
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($questions)): ?>
            <div class="test-header">
                <div class="test-title"><?php echo htmlspecialchars($current_test['name']); ?></div>
                <p>Ответьте на все вопросы и нажмите "Завершить тест" для получения результата.</p>
                <div class="test-meta">
                    <div class="meta-item">
                        <div class="meta-number"><?php echo count($questions); ?></div>
                        <div>Вопросов</div>
                    </div>
                    <?php if ($current_test['time_limit']): ?>
                        <div class="meta-item">
                            <div class="meta-number"><?php echo $current_test['time_limit']; ?></div>
                            <div>Минут</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <form id="testForm" method="POST" action="">
                <input type="hidden" name="start_time" value="<?php echo time(); ?>">
                
                <div class="progress-info">
                    <span id="answeredCount">0</span> из <?php echo count($questions); ?> вопросов отвечено
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%"></div>
                </div>

                <div class="unanswered-warning" id="unansweredWarning">
                    ⚠️ Не все вопросы отвечены. Пожалуйста, ответьте на все вопросы перед завершением теста.
                </div>

                <?php foreach ($questions as $index => $question): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div class="question-number">Вопрос <?php echo $index + 1; ?></div>
                            <div class="question-counter"><?php echo $index + 1; ?> / <?php echo count($questions); ?></div>
                        </div>
                        
                        <div class="question-text"><?php echo htmlspecialchars($question['question']); ?></div>
                        
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="q<?php echo $question['id']; ?>_a" 
                                       name="answers[<?php echo $question['id']; ?>]" value="A" 
                                       onchange="updateProgress()">
                                <label class="option-label" for="q<?php echo $question['id']; ?>_a">
                                    <div class="option-letter">A</div>
                                    <div class="option-text"><?php echo htmlspecialchars($question['option_a']); ?></div>
                                </label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" id="q<?php echo $question['id']; ?>_b" 
                                       name="answers[<?php echo $question['id']; ?>]" value="B" 
                                       onchange="updateProgress()">
                                <label class="option-label" for="q<?php echo $question['id']; ?>_b">
                                    <div class="option-letter">B</div>
                                    <div class="option-text"><?php echo htmlspecialchars($question['option_b']); ?></div>
                                </label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" id="q<?php echo $question['id']; ?>_c" 
                                       name="answers[<?php echo $question['id']; ?>]" value="C" 
                                       onchange="updateProgress()">
                                <label class="option-label" for="q<?php echo $question['id']; ?>_c">
                                    <div class="option-letter">C</div>
                                    <div class="option-text"><?php echo htmlspecialchars($question['option_c']); ?></div>
                                </label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" id="q<?php echo $question['id']; ?>_d" 
                                       name="answers[<?php echo $question['id']; ?>]" value="D" 
                                       onchange="updateProgress()">
                                <label class="option-label" for="q<?php echo $question['id']; ?>_d">
                                    <div class="option-letter">D</div>
                                    <div class="option-text"><?php echo htmlspecialchars($question['option_d']); ?></div>
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="navigation">
                    <a href="tests.php" class="btn btn-secondary">← Отмена</a>
                    <button type="submit" name="submit_test" class="btn btn-success" id="submitBtn">
                        ✅ Завершить тест
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-error">
                В тесте нет вопросов. Обратитесь к преподавателю.
            </div>
            <a href="tests.php" class="btn btn-secondary">← Назад к тестам</a>
        <?php endif; ?>
    </div>

    <script>
        const totalQuestions = <?php echo count($questions); ?>;
        <?php if ($current_test['time_limit']): ?>
        let timeLimit = <?php echo $current_test['time_limit'] * 60; ?>; // в секундах
        let timerInterval;
        <?php endif; ?>

        function updateProgress() {
            const answeredInputs = document.querySelectorAll('input[type="radio"]:checked');
            const answeredCount = answeredInputs.length;
            const progressPercent = (answeredCount / totalQuestions) * 100;
            
            document.getElementById('answeredCount').textContent = answeredCount;
            document.getElementById('progressFill').style.width = progressPercent + '%';
            
            // Показываем/скрываем предупреждение
            const warning = document.getElementById('unansweredWarning');
            if (answeredCount < totalQuestions) {
                warning.style.display = 'block';
            } else {
                warning.style.display = 'none';
            }
        }

        <?php if ($current_test['time_limit']): ?>
        function startTimer() {
            timerInterval = setInterval(function() {
                const minutes = Math.floor(timeLimit / 60);
                const seconds = timeLimit % 60;
                const timerElement = document.getElementById('timer');
                
                timerElement.textContent = `⏰ ${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                // Предупреждения по времени
                if (timeLimit <= 300) { // 5 минут
                    timerElement.className = 'timer danger';
                } else if (timeLimit <= 600) { // 10 минут
                    timerElement.className = 'timer warning';
                }
                
                timeLimit--;
                
                if (timeLimit < 0) {
                    clearInterval(timerInterval);
                    alert('Время вышло! Тест будет автоматически отправлен.');
                    document.getElementById('testForm').submit();
                }
            }, 1000);
        }

        // Запускаем таймер
        startTimer();
        <?php endif; ?>

        // Предотвращаем случайное закрытие страницы
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = '';
            return '';
        });

        // Убираем предупреждение при отправке формы
        document.getElementById('testForm').addEventListener('submit', function() {
            window.removeEventListener('beforeunload', function() {});
        });

        // Проверяем прогресс при загрузке
        updateProgress();
    </script>
</body>
</html>
