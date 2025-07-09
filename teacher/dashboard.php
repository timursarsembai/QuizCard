<?php
// Включаем отображение ошибок для диагностики
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

    // Получаем статистику
    $total_decks = $deck->getDecksCountByTeacher($teacher_id);
    $total_tests = $test->getTestsCountByTeacher($teacher_id);
    $total_students = $user->getStudentsCountByTeacher($teacher_id);
    $total_words = $deck->getWordsCountByTeacher($teacher_id);

    // Получаем последние действия (например, последние пройденные тесты)
    $recent_activities = $test->getRecentTestAttemptsByTeacher($teacher_id, 5);

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

$page_title = "Панель управления";
$page_icon = "fas fa-tachometer-alt";
require_once 'header.php';
?>
<style>
    /* Стили для dashboard.php */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        font-size: 2.5rem;
        color: #667eea;
    }

    .stat-info .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: #333;
    }

    .stat-info .stat-label {
        color: #666;
        font-size: 0.9rem;
    }

    .activity-list {
        list-style: none;
        padding-left: 0;
    }

    .activity-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }
    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-item .student-info {
        font-weight: 500;
    }
    .activity-item .test-info {
        color: #555;
        margin-left: 0.5rem;
    }
    .activity-item .score {
        font-weight: bold;
        color: #667eea;
    }
    .activity-item .timestamp {
        font-size: 0.85rem;
        color: #888;
        margin-left: 1rem;
    }

</style>

<div class="container">
    <?php include 'language_switcher.php'; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-layer-group stat-icon"></i>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_decks; ?></div>
                <div class="stat-label" data-translate-key="decks">Колод</div>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-file-alt stat-icon"></i>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_tests; ?></div>
                <div class="stat-label" data-translate-key="tests">Тестов</div>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-graduate stat-icon"></i>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label" data-translate-key="students">Учеников</div>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-spell-check stat-icon"></i>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_words; ?></div>
                <div class="stat-label" data-translate-key="words">Слов</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 data-translate-key="recent_student_activity"><i class="fas fa-history"></i> Последние действия учеников</h2>
        <?php if (!empty($recent_activities)): ?>
            <ul class="activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                    <li class="activity-item">
                        <div>
                            <span class="student-info"><?php echo htmlspecialchars($activity['student_name']); ?></span>
                            <span class="test-info">
                                <span data-translate-key="activity_took_test">прошел(а) тест</span> "<?php echo htmlspecialchars($activity['test_name']); ?>"
                            </span>
                        </div>
                        <div>
                            <span class="score"><span data-translate-key="score">Результат</span>: <?php echo round($activity['score'], 1); ?>%</span>
                            <span class="timestamp"><?php echo date('d.m.Y H:i', strtotime($activity['completed_at'])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="empty-state">
                <p data-translate-key="no_student_activity">Пока нет никаких действий от учеников.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
