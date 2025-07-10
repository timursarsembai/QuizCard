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

// Проверяем, что deck_id принадлежит данному преподавателю
if (!isset($_GET['deck_id'])) {
    header("Location: decks.php");
    exit();
}

$deck_id = $_GET['deck_id'];
$current_deck = $deck->getDeckById($deck_id, $teacher_id);

if (!$current_deck) {
    header("Location: decks.php");
    exit();
}

// Обработка создания нового теста
if ($_POST && isset($_POST['create_test'])) {
    $test_name = trim($_POST['test_name']);
    $questions_count = intval($_POST['questions_count']);
    $time_limit = intval($_POST['time_limit']) ?: null;
    
    if ($test_name && $questions_count > 0) {
        $test_id = $test->createTest($deck_id, $test_name, $questions_count, $time_limit);
        if ($test_id) {
            $success = "Тест успешно создан!";
            // Перенаправляем на редактирование теста
            header("Location: test_edit.php?test_id=$test_id");
            exit();
        } else {
            $error = "Ошибка при создании теста";
        }
    } else {
        $error = "Заполните все обязательные поля";
    }
}

// Обработка удаления теста
if ($_GET && isset($_GET['delete_test'])) {
    $test_id = $_GET['delete_test'];
    if ($test->deleteTest($test_id, $teacher_id)) {
        $success = "Тест успешно удален!";
    } else {
        $error = "Ошибка при удалении теста";
    }
}

// Получаем все тесты для колоды
$tests = $test->getTestsByDeck($deck_id);
$words = $vocabulary->getVocabularyByDeck($deck_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="test_manager_title">QuizCard - Управление тестами</title>
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
        }

        .btn-primary:hover {
            background: #218838;
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

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
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

        .deck-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid;
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

        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="number"]:focus, select:focus {
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

        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .test-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .test-header {
            margin-bottom: 1rem;
        }

        .test-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .test-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        .test-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .test-actions .btn {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
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

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-card .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .tests-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="test_manager_title">🧪 Управление тестами</h1>
                <div class="breadcrumb">
                    <a href="decks.php" data-translate-key="nav_decks">Колоды</a> → <span data-translate-key="test_manager_breadcrumb">Тесты колоды</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="decks.php" class="btn" data-translate-key="back_button">← Назад</a>
                <a href="../logout.php" class="btn" data-translate-key="logout_button">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php include 'language_switcher.php'; ?>
        
        <div class="deck-info" style="border-left-color: <?php echo htmlspecialchars($current_deck['color']); ?>">
            <h2><span data-translate-key="deck_prefix">Колода:</span> <?php echo htmlspecialchars($current_deck['name']); ?></h2>
            <?php if ($current_deck['description']): ?>
                <p><?php echo htmlspecialchars($current_deck['description']); ?></p>
            <?php endif; ?>
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
                <div class="stat-label" data-translate-key="words_in_deck">Слов в колоде</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($tests); ?></div>
                <div class="stat-label" data-translate-key="tests_created">Тестов создано</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo array_sum(array_column($tests, 'attempts_count')); ?></div>
                <div class="stat-label" data-translate-key="total_attempts">Всего попыток</div>
            </div>
        </div>

        <?php if (count($words) < 4): ?>
            <div class="alert alert-error">
                <strong data-translate-key="warning_title">Внимание!</strong> <span data-translate-key="minimum_words_required">Для создания теста необходимо минимум 4 слова в колоде.</span>
                <span data-translate-key="current_words_count">Сейчас в колоде</span> <?php echo count($words); ?> <span data-translate-key="words_plural">слов(а)</span>. 
                <a href="vocabulary.php?deck_id=<?php echo $deck_id; ?>" data-translate-key="add_words_link">Добавить слова</a>
            </div>
        <?php else: ?>
            <div class="card">
                <h2 data-translate-key="create_new_test">Создать новый тест</h2>
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="test_name" data-translate-key="test_name_label">Название теста:</label>
                            <input type="text" id="test_name" name="test_name" required 
                                   data-translate-key="test_name_placeholder" placeholder="Например: Тест по базовым словам">
                        </div>
                        <div class="form-group">
                            <label for="questions_count" data-translate-key="questions_count_label">Количество вопросов:</label>
                            <input type="number" id="questions_count" name="questions_count" 
                                   min="4" max="<?php echo count($words); ?>" value="10" required>
                            <small style="color: #666; font-size: 0.9em;"><span data-translate-key="maximum_prefix">Максимум:</span> <?php echo count($words); ?> <span data-translate-key="words_count_suffix">(количество слов в колоде)</span></small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="time_limit" data-translate-key="time_limit_label">Ограничение по времени (минуты, опционально):</label>
                        <input type="number" id="time_limit" name="time_limit" min="1" max="60" 
                               data-translate-key="time_limit_placeholder" placeholder="Оставьте пустым для неограниченного времени">
                    </div>
                    <button type="submit" name="create_test" class="btn btn-primary" data-translate-key="create_test_button">🧪 Создать тест</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 data-translate-key="created_tests">Созданные тесты</h2>
            <?php if (empty($tests)): ?>
                <div class="empty-state">
                    <h3 data-translate-key="no_tests_title">📝 Тесты не созданы</h3>
                    <p data-translate-key="no_tests_description">Создайте первый тест для проверки знаний учеников.</p>
                </div>
            <?php else: ?>
                <div class="tests-grid">
                    <?php foreach ($tests as $test_item): ?>
                        <div class="test-card">
                            <div class="test-header">
                                <div class="test-name"><?php echo htmlspecialchars($test_item['name']); ?></div>
                            </div>
                            
                            <div class="test-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $test_item['questions_count']; ?></div>
                                    <div class="stat-label" data-translate-key="questions_stat">Вопросов</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $test_item['time_limit'] ?: '∞'; ?></div>
                                    <div class="stat-label" data-translate-key="minutes_stat">Минут</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $test_item['attempts_count'] ?: 0; ?></div>
                                    <div class="stat-label" data-translate-key="attempts_stat">Попыток</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo date('d.m', strtotime($test_item['created_at'])); ?></div>
                                    <div class="stat-label" data-translate-key="created_stat_short">Создан</div>
                                </div>
                            </div>
                            
                            <div class="test-actions">
                                <a href="test_edit.php?test_id=<?php echo $test_item['id']; ?>" 
                                   class="btn btn-info" data-translate-key="edit_questions_tooltip" title="Редактировать вопросы">✏️</a>
                                <a href="test_preview.php?test_id=<?php echo $test_item['id']; ?>" 
                                   class="btn btn-success" data-translate-key="preview_tooltip" title="Предварительный просмотр">👁️</a>
                                <a href="test_results.php?test_id=<?php echo $test_item['id']; ?>" 
                                   class="btn btn-warning" data-translate-key="results_tooltip" title="Результаты учеников">📊</a>
                                <a href="?deck_id=<?php echo $deck_id; ?>&delete_test=<?php echo $test_item['id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Вы уверены, что хотите удалить этот тест?')"
                                   data-confirm-key="delete_test_confirm"
                                   data-translate-key="delete_tooltip" title="Удалить тест">🗑️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
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

        // Обновление максимального количества вопросов
        const wordsCount = <?php echo count($words); ?>;
        const questionsInput = document.getElementById('questions_count');
        
        if (questionsInput) {
            questionsInput.addEventListener('input', function() {
                if (parseInt(this.value) > wordsCount) {
                    this.value = wordsCount;
                }
            });
        }
    </script>
</body>
</html>
