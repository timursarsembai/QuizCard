<?php
// Включаем отображение ошибок для диагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../includes/translations.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Database connection failed: " . $database->getError());
    }
    
    $user = new User($db);
    $deck = new Deck($db);

    if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
        header("Location: ../index.php");
        exit();
    }

    $teacher_id = $_SESSION['user_id'];
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Обработка создания новой колоды
if ($_POST && isset($_POST['create_deck'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $color = $_POST['color'] ?: '#667eea';
    $daily_word_limit = intval($_POST['daily_word_limit']) ?: 20;
    
    if ($deck->createDeck($teacher_id, $name, $description, $color, $daily_word_limit)) {
        $success = "deck_created_success";
    } else {
        $error = "deck_create_error";
    }
}

// Обработка удаления колоды
if ($_GET && isset($_GET['delete_deck'])) {
    $deck_id = $_GET['delete_deck'];
    if ($deck->deleteDeck($deck_id, $teacher_id)) {
        $success = "deck_deleted_success";
    } else {
        $error = "deck_delete_error";
    }
}

try {
    $decks = $deck->getDecksByTeacher($teacher_id);
    $students = $user->getStudentsByTeacher($teacher_id);
} catch (Exception $e) {
    die("Error getting data: " . $e->getMessage());
}

$page_title = 'Управление колодами';
$page_icon = '📚';
include 'header.php';
?>

<style>
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

    input[type="text"], input[type="number"], input[type="color"], textarea {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e1e1e1;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    input[type="text"]:focus, input[type="number"]:focus, input[type="color"]:focus, textarea:focus {
        outline: none;
        border-color: #667eea;
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    .decks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .deck-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
        border-left: 5px solid;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .deck-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .deck-header {
        margin-bottom: 1rem;
    }

    .deck-name {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
    }

    .deck-description {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.4;
        margin-bottom: 1rem;
    }

    .deck-stats {
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

    .stat-number {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #666;
    }

    .deck-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }
    
    .deck-actions .btn {
        flex-grow: 1;
        text-align: center;
    }

    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-block;
        vertical-align: middle;
        margin-left: 0.5rem;
        border: 2px solid #ddd;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #666;
    }

    .empty-state h3 {
        color: #667eea;
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .form-grid, .decks-grid {
            grid-template-columns: 1fr;
        }
        .deck-stats {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
    }
</style>

<div class="container">
    <?php include 'language_switcher.php'; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success" data-translate-key="<?php echo $success; ?>">Колода успешно создана!</div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" data-translate-key="<?php echo $error; ?>">Ошибка при создании колоды</div>
    <?php endif; ?>

    <div class="card">
        <h2 data-translate-key="create_new_deck">Создать новую колоду</h2>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name" data-translate-key="deck_name">Название колоды:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="color" data-translate-key="deck_color">Цвет колоды:</label>
                    <div style="display: flex; align-items: center;">
                        <input type="color" id="color" name="color" value="#667eea" 
                               onchange="updateColorPreview(this.value)">
                        <span class="color-preview" id="colorPreview" style="background-color: #667eea;"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="daily_word_limit" data-translate-key="daily_word_limit">Дневной лимит новых слов:</label>
                    <input type="number" id="daily_word_limit" name="daily_word_limit" value="20" min="1" max="100" 
                           title="Максимальное количество новых слов, которые студент может изучить за день">
                    <small style="color: #666; display: block; margin-top: 5px;" data-translate-key="daily_word_limit_help">
                        Ограничивает количество новых слов в день (повторения не ограничиваются)
                    </small>
                </div>
            </div>
            <div class="form-group">
                <label for="description" data-translate-key="deck_description">Описание (опционально):</label>
                <textarea id="description" name="description" data-translate-key="deck_description_placeholder" placeholder="Краткое описание темы колоды..."></textarea>
            </div>
            <button type="submit" name="create_deck" class="btn btn-primary" data-translate-key="create_deck_button">Создать колоду</button>
        </form>
    </div>

    <div class="card">
        <h2 data-translate-key="my_decks">Мои колоды</h2>
        <?php if (empty($decks)): ?>
            <div class="empty-state">
                <h3 data-translate-key="empty_deck_title">📝 Колоды не созданы</h3>
                <p data-translate-key="empty_deck_text">Создайте первую колоду для организации словарей по темам.</p>
            </div>
        <?php else: ?>
            <div class="decks-grid">
                <?php foreach ($decks as $deck_item): ?>
                    <div class="deck-card" style="border-left-color: <?php echo htmlspecialchars($deck_item['color']); ?>">
                        <div class="deck-header">
                            <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                            <?php if ($deck_item['description']): ?>
                                <div class="deck-description"><?php echo htmlspecialchars($deck_item['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="deck-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $deck_item['word_count'] ?: 0; ?></div>
                                <div class="stat-label" data-translate-key="words_stat">Слов</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $deck_item['assigned_students'] ?: 0; ?></div>
                                <div class="stat-label" data-translate-key="students_stat">Учеников</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $deck_item['daily_word_limit'] ?: 20; ?></div>
                                <div class="stat-label" data-translate-key="words_per_day">Слов/день</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo date('d.m', strtotime($deck_item['created_at'])); ?></div>
                                <div class="stat-label" data-translate-key="created_stat">Создано</div>
                            </div>
                        </div>
                        
                        <div class="deck-actions">
                            <a href="vocabulary.php?deck_id=<?php echo $deck_item['id']; ?>" 
                               class="btn btn-primary" data-translate-key="manage_words" title="Управление словами">✏️</a>
                            <a href="import_words.php?deck_id=<?php echo $deck_item['id']; ?>" 
                               class="btn btn-info" data-translate-key="import_from_file" title="Импорт из файла">📤</a>
                            <a href="deck_students.php?deck_id=<?php echo $deck_item['id']; ?>" 
                               class="btn" data-translate-key="manage_students" title="Управление учениками">👥</a>
                            <a href="?delete_deck=<?php echo $deck_item['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Вы уверены, что хотите удалить эту колоду?')"
                               data-translate-key="delete_deck" 
                               data-confirm-key="delete_deck_confirm"
                               title="Удалить колоду">🗑️</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateColorPreview(color) {
        document.getElementById('colorPreview').style.backgroundColor = color;
    }

    // Автоматическое скрытие уведомлений через 5 секунд
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
