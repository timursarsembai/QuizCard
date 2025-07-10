<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../classes/Test.php';
require_once '../classes/Vocabulary.php';
require_once '../includes/translations.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);
$test = new Test($db);
$vocabulary = new Vocabulary($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success = null;
$error = null;

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

// Обработка сохранения теста
if ($_POST && isset($_POST['save_test'])) {
    $test_name = trim($_POST['test_name']);
    $time_limit = intval($_POST['time_limit']) ?: null;
    
    // Обновляем основную информацию теста
    if ($test->updateTestInfo($test_id, $test_name, $time_limit)) {
        // Обрабатываем вопросы
        $questions_data = [];
        if (isset($_POST['questions']) && is_array($_POST['questions'])) {
            foreach ($_POST['questions'] as $q_data) {
                if (!empty($q_data['question']) && !empty($q_data['correct_answer'])) {
                    $questions_data[] = [
                        'question' => trim($q_data['question']),
                        'option_a' => trim($q_data['option_a']),
                        'option_b' => trim($q_data['option_b']),
                        'option_c' => trim($q_data['option_c']),
                        'option_d' => trim($q_data['option_d']),
                        'correct_answer' => $q_data['correct_answer']
                    ];
                }
            }
        }
        
        if (!empty($questions_data)) {
            if ($test->updateTestQuestions($test_id, $questions_data)) {
                $success = $translations[$_SESSION['language'] ?? 'ru']['test_saved_success'] ?? "Тест успешно сохранен!";
                // Обновляем данные теста
                $current_test = $test->getTestById($test_id);
            } else {
                $error = $translations[$_SESSION['language'] ?? 'ru']['questions_update_error'] ?? "Ошибка при сохранении вопросов";
            }
        } else {
            $error = $translations[$_SESSION['language'] ?? 'ru']['add_one_question_error'] ?? "Добавьте хотя бы один вопрос";
        }
    } else {
        $error = $translations[$_SESSION['language'] ?? 'ru']['test_save_error'] ?? "Ошибка при обновлении теста";
    }
}

// Обработка генерации вопросов
if ($_POST && isset($_POST['generate_questions'])) {
    $questions_count = intval($_POST['questions_count']);
    if ($questions_count > 0) {
        if ($test->generateQuestionsForTest($test_id, $questions_count)) {
            $success = $translations[$_SESSION['language'] ?? 'ru']['questions_generated_success'] ?? "Вопросы успешно сгенерированы!";
            // Обновляем данные теста
            $current_test = $test->getTestById($test_id);
        } else {
            $error = $translations[$_SESSION['language'] ?? 'ru']['questions_generate_error'] ?? "Ошибка при генерации вопросов";
        }
    }
}

// Получаем вопросы теста
$questions = $test->getTestQuestions($test_id);
$words = $vocabulary->getVocabularyByDeck($current_test['deck_id']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="test_edit_title">QuizCard - Редактирование теста</title>
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .test-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid #667eea;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .question-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .question-number {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .option-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .option-input {
            flex: 1;
        }

        .correct-answer-select {
            width: 150px;
        }

        .generate-section {
            background: #e8f4fd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #17a2b8;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="test_edit_title">✏️ Редактирование теста</h1>
                <div class="breadcrumb">
                    <a href="decks.php" data-translate-key="test_edit_breadcrumb_decks">Колоды</a> → 
                    <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" data-translate-key="test_edit_breadcrumb_tests">Тесты</a> → 
                    <span data-translate-key="test_edit_breadcrumb_edit">Редактирование</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" class="btn" data-translate-key="back_button">← Назад</a>
                <a href="../logout.php" class="btn" data-translate-key="logout_button">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php include 'language_switcher.php'; ?>
        
        <div class="test-info">
            <h2><span data-translate-key="test_info_prefix">Тест:</span> <?php echo htmlspecialchars($current_test['name']); ?></h2>
            <p><span data-translate-key="deck_info_prefix">Колода:</span> <?php echo htmlspecialchars($current_deck['name']); ?></p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($words); ?></div>
                <div class="stat-label" data-translate-key="words_in_deck_stat">Слов в колоде</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($questions); ?></div>
                <div class="stat-label" data-translate-key="questions_in_test_stat">Вопросов в тесте</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $current_test['time_limit'] ?: '∞'; ?></div>
                <div class="stat-label" data-translate-key="minutes_for_test_stat">Минут на тест</div>
            </div>
        </div>

        <?php if (empty($questions)): ?>
            <div class="generate-section">
                <h3 data-translate-key="autogeneration_title">🤖 Автогенерация вопросов</h3>
                <p data-translate-key="autogeneration_description">Система может автоматически создать вопросы на основе слов из колоды. 
                   Вы сможете отредактировать их после генерации.</p>
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="questions_count" data-translate-key="questions_count_label">Количество вопросов:</label>
                            <input type="number" id="questions_count" name="questions_count" 
                                   min="1" max="<?php echo count($words); ?>" 
                                   value="<?php echo min(10, count($words)); ?>" required>
                        </div>
                    </div>
                    <button type="submit" name="generate_questions" class="btn btn-info" data-translate-key="generate_questions_button">🤖 Сгенерировать вопросы</button>
                </form>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="card">
                <h2 data-translate-key="test_settings_title">Настройки теста</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="test_name" data-translate-key="test_name_label">Название теста:</label>
                        <input type="text" id="test_name" name="test_name" 
                               value="<?php echo htmlspecialchars($current_test['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="time_limit" data-translate-key="time_limit_label">Ограничение по времени (минуты):</label>
                        <input type="number" id="time_limit" name="time_limit" 
                               min="1" max="60" value="<?php echo $current_test['time_limit']; ?>"
                               data-translate-key="time_limit_placeholder" placeholder="Оставьте пустым для неограниченного времени">
                    </div>
                </div>
            </div>

            <?php if (!empty($questions)): ?>
                <div class="card">
                    <h2 data-translate-key="test_questions_title">Вопросы теста</h2>
                    <?php foreach ($questions as $index => $question): ?>
                        <div class="question-card">
                            <div class="question-header">
                                <span class="question-number"><span data-translate-key="question_prefix">Вопрос</span> <?php echo $index + 1; ?></span>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="removeQuestion(<?php echo $index; ?>)">🗑️</button>
                            </div>
                            
                            <div class="form-group">
                                <label data-translate-key="question_label">Вопрос:</label>
                                <textarea name="questions[<?php echo $index; ?>][question]" required
                                          data-translate-key="question_placeholder" placeholder="Введите текст вопроса..."><?php echo htmlspecialchars($question['question']); ?></textarea>
                            </div>
                            
                            <div class="options-grid">
                                <div class="option-group">
                                    <label>A:</label>
                                    <input type="text" name="questions[<?php echo $index; ?>][option_a]" 
                                           class="option-input" value="<?php echo htmlspecialchars($question['option_a']); ?>" required>
                                </div>
                                <div class="option-group">
                                    <label>B:</label>
                                    <input type="text" name="questions[<?php echo $index; ?>][option_b]" 
                                           class="option-input" value="<?php echo htmlspecialchars($question['option_b']); ?>" required>
                                </div>
                                <div class="option-group">
                                    <label>C:</label>
                                    <input type="text" name="questions[<?php echo $index; ?>][option_c]" 
                                           class="option-input" value="<?php echo htmlspecialchars($question['option_c']); ?>" required>
                                </div>
                                <div class="option-group">
                                    <label>D:</label>
                                    <input type="text" name="questions[<?php echo $index; ?>][option_d]" 
                                           class="option-input" value="<?php echo htmlspecialchars($question['option_d']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label data-translate-key="correct_answer_label">Правильный ответ:</label>
                                <select name="questions[<?php echo $index; ?>][correct_answer]" class="correct-answer-select" required>
                                    <option value="A" <?php echo $question['correct_answer'] === 'A' ? 'selected' : ''; ?>>A</option>
                                    <option value="B" <?php echo $question['correct_answer'] === 'B' ? 'selected' : ''; ?>>B</option>
                                    <option value="C" <?php echo $question['correct_answer'] === 'C' ? 'selected' : ''; ?>>C</option>
                                    <option value="D" <?php echo $question['correct_answer'] === 'D' ? 'selected' : ''; ?>>D</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <button type="button" class="btn btn-secondary" onclick="addQuestion()" data-translate-key="add_question_button">➕ Добавить вопрос</button>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" name="save_test" class="btn btn-primary" data-translate-key="save_test_button">💾 Сохранить тест</button>
                <a href="test_preview.php?test_id=<?php echo $test_id; ?>" class="btn btn-info" data-translate-key="preview_test_button">👁️ Предварительный просмотр</a>
                <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" class="btn btn-secondary" data-translate-key="cancel_button">❌ Отмена</a>
            </div>
        </form>
    </div>

    <script>
        let questionIndex = <?php echo count($questions); ?>;

        function addQuestion() {
            const questionsContainer = document.querySelector('.card:last-of-type');
            const addButton = questionsContainer.querySelector('button[onclick="addQuestion()"]');
            
            const questionCard = document.createElement('div');
            questionCard.className = 'question-card';
            questionCard.innerHTML = `
                <div class="question-header">
                    <span class="question-number"><span data-translate-key="question_prefix">Вопрос</span> ${questionIndex + 1}</span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeQuestion(${questionIndex})">🗑️</button>
                </div>
                
                <div class="form-group">
                    <label data-translate-key="question_label">Вопрос:</label>
                    <textarea name="questions[${questionIndex}][question]" required
                              data-translate-key="question_placeholder" placeholder="Введите текст вопроса..."></textarea>
                </div>
                
                <div class="options-grid">
                    <div class="option-group">
                        <label>A:</label>
                        <input type="text" name="questions[${questionIndex}][option_a]" class="option-input" required>
                    </div>
                    <div class="option-group">
                        <label>B:</label>
                        <input type="text" name="questions[${questionIndex}][option_b]" class="option-input" required>
                    </div>
                    <div class="option-group">
                        <label>C:</label>
                        <input type="text" name="questions[${questionIndex}][option_c]" class="option-input" required>
                    </div>
                    <div class="option-group">
                        <label>D:</label>
                        <input type="text" name="questions[${questionIndex}][option_d]" class="option-input" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label data-translate-key="correct_answer_label">Правильный ответ:</label>
                    <select name="questions[${questionIndex}][correct_answer]" class="correct-answer-select" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            `;
            
            addButton.parentNode.insertBefore(questionCard, addButton);
            questionIndex++;
        }

        function removeQuestion(index) {
            const questionCard = document.querySelector(`[onclick="removeQuestion(${index})"]`).closest('.question-card');
            // Получаем текст подтверждения из переводов
            const confirmMessage = translations[currentLang] && translations[currentLang]['delete_question_confirm'] 
                ? translations[currentLang]['delete_question_confirm'] 
                : 'Вы уверены, что хотите удалить этот вопрос?';
            
            if (confirm(confirmMessage)) {
                questionCard.remove();
                updateQuestionNumbers();
            }
        }

        function updateQuestionNumbers() {
            const questionCards = document.querySelectorAll('.question-card');
            questionCards.forEach((card, index) => {
                const numberSpan = card.querySelector('.question-number');
                const questionText = translations[currentLang] && translations[currentLang]['question_prefix'] 
                    ? translations[currentLang]['question_prefix'] 
                    : 'Вопрос';
                numberSpan.innerHTML = `<span data-translate-key="question_prefix">${questionText}</span> ${index + 1}`;
            });
        }

        // Автоматическое скрытие уведомлений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
