<?php
// Включаем отображение ошибок для диагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Отключаем кэширование
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../classes/Test.php';
require_once '../includes/translations.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed: " . $database->getError());
    }
    
    $user = new User($db);
    $deck = new Deck($db);
    $test = new Test($db);

    if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
        header("Location: ../index.php");
        exit();
    }

    $teacher_id = $_SESSION['user_id'];
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Обработка создания нового теста
if ($_POST && isset($_POST['create_test'])) {
    $deck_id = $_POST['deck_id'];
    $test_name = trim($_POST['test_name']);
    $questions_count = intval($_POST['questions_count']) ?: 10;
    $time_limit = intval($_POST['time_limit']) ?: null;
    
    // Проверяем, что колода принадлежит преподавателю
    $deck_info = $deck->getDeckById($deck_id, $teacher_id);
    if ($deck_info && $test_name) {
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

// Получаем все колоды преподавателя
try {
    $decks = $deck->getDecksByTeacher($teacher_id);
} catch (Exception $e) {
    die("Error getting decks: " . $e->getMessage());
}

// Получаем все тесты преподавателя
try {
    $all_tests = $test->getTestsByTeacher($teacher_id);
} catch (Exception $e) {
    die("Error getting tests: " . $e->getMessage());
}

// Добавляем счетчик тестов для каждой колоды
foreach ($decks as &$deck_item) {
    $deck_item['tests_count'] = 0;
    foreach ($all_tests as $test_item) {
        if ($test_item['deck_id'] == $deck_item['id']) {
            $deck_item['tests_count']++;
        }
    }
}
unset($deck_item); // Важно! Удаляем ссылку на последний элемент

// Получаем общую статистику
$total_tests = count($all_tests);
$total_attempts = 0;
foreach ($all_tests as $test_item) {
    $total_attempts += isset($test_item['attempts_count']) ? $test_item['attempts_count'] : 0;
}

$page_title = "Управление тестами";
$page_icon = "fas fa-file-alt";
require_once 'header.php';
?>
<style>
    a.btn.btn-success {
        background-color: purple;
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

    .stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
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

    .deck-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .deck-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease;
    }

    .deck-card:hover {
        transform: translateY(-5px);
    }

    .deck-header {
        padding: 1.5rem;
        color: white;
        position: relative;
    }

    .deck-info h3 {
        margin-bottom: 0.5rem;
        font-size: 1.2rem;
    }

    .deck-description {
        opacity: 0.9;
        font-size: 0.9rem;
    }

    .deck-content {
        padding: 1.5rem;
    }

    .deck-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .stat {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
    }

    .stat-text {
        font-size: 0.8rem;
        color: #666;
    }

    .deck-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .tests-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .test-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
        border-left: 5px solid;
    }

    .test-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .test-header {
        margin-bottom: 1rem;
    }

    .test-name {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .deck-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.8rem;
        color: white;
        font-weight: 500;
    }

    .test-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin: 1rem 0;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .stat-item {
        text-align: center;
    }

    .test-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .test-actions .btn {
        flex: 1;
        text-align: center;
        min-width: 80px;
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .deck-grid {
            grid-template-columns: 1fr;
        }

        .stats {
            grid-template-columns: 1fr 1fr;
        }

        .deck-actions {
            flex-direction: column;
        }
        
        .tests-grid {
            grid-template-columns: 1fr;
        }
        
        .test-actions {
            flex-direction: column;
        }

        .test-actions .btn {
            flex: none;
        }
    }
</style>

<div class="container">
    <?php include 'language_switcher.php'; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($decks); ?></div>
            <div class="stat-label" data-translate-key="total_decks">Всего колод</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_tests; ?></div>
            <div class="stat-label" data-translate-key="total_tests">Всего тестов</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_attempts; ?></div>
            <div class="stat-label" data-translate-key="total_attempts">Всего попыток</div>
        </div>
    </div>

    <div class="card">
        <h2 data-translate-key="create_new_test">📝 Создать новый тест</h2>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="deck_id" data-translate-key="select_deck">Выберите колоду:</label>
                    <select name="deck_id" id="deck_id" required>
                        <option value="" data-translate-key="select_deck_option">-- Выберите колоду --</option>
                        <?php foreach ($decks as $deck_item): ?>
                            <option value="<?php echo $deck_item['id']; ?>" data-words-count="<?php echo $deck_item['word_count']; ?>">
                                <?php echo htmlspecialchars($deck_item['name']); ?> 
                                (<span class="words-count"><?php echo $deck_item['word_count']; ?></span> <span data-translate-key="words_plural">слов</span>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="test_name" data-translate-key="test_name">Название теста:</label>
                    <input type="text" name="test_name" id="test_name" data-translate-key="test_name_placeholder" placeholder="Введите название теста" required>
                </div>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label for="questions_count" data-translate-key="questions_count">Количество вопросов:</label>
                    <input type="number" name="questions_count" id="questions_count" min="1" max="50" value="10" required>
                </div>
                <div class="form-group">
                    <label for="time_limit" data-translate-key="time_limit">Ограничение времени (минуты):</label>
                    <input type="number" name="time_limit" id="time_limit" min="1" max="60" data-translate-key="time_limit_placeholder" placeholder="Оставьте пустым для неограниченного времени">
                </div>
            </div>
            <button type="submit" name="create_test" class="btn btn-primary" data-translate-key="create_test_button">✨ Создать тест</button>
        </form>
    </div>

    <div class="card">
        <h2 data-translate-key="tests_by_decks">📚 Тесты по колодам</h2>
        
        <?php if (!empty($decks)): ?>
            <div class="deck-grid">
                <?php foreach ($decks as $deck_item): ?>
                    <div class="deck-card">
                        <div class="deck-header" style="background: <?php echo htmlspecialchars($deck_item['color']); ?>">
                            <div class="deck-info">
                                <h3><?php echo htmlspecialchars($deck_item['name']); ?></h3>
                                <div class="deck-description">
                                    <?php echo htmlspecialchars($deck_item['description']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="deck-content">
                            <div class="deck-stats">
                                <div class="stat">
                                    <div class="stat-value"><?php echo $deck_item['word_count']; ?></div>
                                    <div class="stat-text" data-translate-key="words_in_deck">Слов в колоде</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-value"><?php echo $deck_item['tests_count']; ?></div>
                                    <div class="stat-text" data-translate-key="tests_created">Тестов создано</div>
                                </div>
                            </div>
                            <div class="deck-actions">
                                <a href="test_manager.php?deck_id=<?php echo $deck_item['id']; ?>" 
                                   class="btn btn-primary" 
                                   title="Управление тестами">
                                    🧪 <span data-translate-key="tests_button">Тесты</span> (<?php echo $deck_item['tests_count']; ?>)
                                </a>
                                <?php if ($deck_item['word_count'] > 0): ?>
                                    <a href="test_manager.php?deck_id=<?php echo $deck_item['id']; ?>&create=1" 
                                       class="btn btn-success" 
                                       title="Создать тест">
                                        ➕ <span data-translate-key="create_test_button">Создать тест</span>
                                    </a>
                                <?php else: ?>
                                    <span class="btn btn-secondary" 
                                          style="opacity: 0.6;" 
                                          title="Добавьте слова в колоду">
                                        ➕ <span data-translate-key="create_test_button">Создать тест</span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3 data-translate-key="no_decks_title">📚 Нет колод</h3>
                <p data-translate-key="no_decks_text">Создайте колоды и добавьте в них слова, чтобы создавать тесты.</p>
                <a href="decks.php" class="btn btn-primary" style="margin-top: 1rem;" data-translate-key="go_to_decks">
                    📚 Перейти к колодам
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Раздел всех тестов -->
    <div class="card">
        <h2 data-translate-key="all_tests">📋 Все тесты</h2>
        <?php if (!empty($all_tests)): ?>
            <div class="tests-grid">
                <?php foreach ($all_tests as $test_item): ?>
                    <div class="test-card" style="border-left-color: <?php echo htmlspecialchars($test_item['deck_color']); ?>;">
                        <div class="test-header">
                            <div class="test-name"><?php echo htmlspecialchars($test_item['name']); ?></div>
                            <span class="deck-badge" style="background-color: <?php echo htmlspecialchars($test_item['deck_color']); ?>;">
                                <?php echo htmlspecialchars($test_item['deck_name']); ?>
                            </span>
                        </div>
                        
                        <div class="test-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $test_item['questions_count']; ?></div>
                                <div class="stat-label" data-translate-key="questions">Вопросов</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo isset($test_item['attempts_count']) ? $test_item['attempts_count'] : 0; ?></div>
                                <div class="stat-label" data-translate-key="attempts">Попыток</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo isset($test_item['unique_students']) ? $test_item['unique_students'] : 0; ?></div>
                                <div class="stat-label" data-translate-key="students">Учеников</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php echo (isset($test_item['avg_score']) && $test_item['avg_score']) ? round($test_item['avg_score'], 1) . '%' : '—'; ?>
                                </div>
                                <div class="stat-label" data-translate-key="avg_score">Ср. балл</div>
                            </div>
                        </div>

                        <div style="margin: 1rem 0; padding: 0.5rem; background: #e9ecef; border-radius: 5px; font-size: 0.9rem;">
                            <div><strong data-translate-key="time_label">Время:</strong> 
                                <?php echo $test_item['time_limit'] ? $test_item['time_limit'] . ' <span data-translate-key="time_limit_minutes">минут</span>' : '<span data-translate-key="no_time_limit">Без ограничения</span>'; ?>
                            </div>
                            <div><strong data-translate-key="created_label">Создан:</strong> 
                                <?php echo date('d.m.Y H:i', strtotime($test_item['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div class="test-actions">
                            <a href="test_edit.php?test_id=<?php echo $test_item['id']; ?>" class="btn btn-primary">
                                ✏️ <span data-translate-key="edit_test">Редактировать</span>
                            </a>
                            <a href="test_preview.php?test_id=<?php echo $test_item['id']; ?>" class="btn btn-info">
                                👁️ <span data-translate-key="preview_test">Предпросмотр</span>
                            </a>
                            <a href="test_results.php?test_id=<?php echo $test_item['id']; ?>" class="btn btn-success">
                                📊 <span data-translate-key="test_results">Результаты</span>
                            </a>
                            <a href="?delete_test=<?php echo $test_item['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Вы уверены, что хотите удалить этот тест? Это действие нельзя отменить.')"
                               data-confirm-key="delete_test_confirm">
                               🗑️ <span data-translate-key="delete_test">Удалить</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3 data-translate-key="no_tests_title">🎯 Нет тестов</h3>
                <p data-translate-key="no_tests_text">Создайте первый тест, используя форму выше</p>
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

    // Валидация формы
    document.querySelector('form').addEventListener('submit', function(e) {
        const deckSelect = document.getElementById('deck_id');
        const testName = document.getElementById('test_name');
        const questionsCount = document.getElementById('questions_count');

        if (!deckSelect.value) {
            alert('Выберите колоду для создания теста');
            e.preventDefault();
            return;
        }

        if (!testName.value.trim()) {
            alert('Введите название теста');
            e.preventDefault();
            return;
        }

        if (parseInt(questionsCount.value) < 1) {
            alert('Количество вопросов должно быть больше 0');
            e.preventDefault();
            return;
        }
    });
</script>
</body>
</html>
