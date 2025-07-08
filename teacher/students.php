<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Vocabulary.php';
require_once '../classes/Deck.php';
require_once '../classes/Test.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$vocabulary = new Vocabulary($db);
$deck = new Deck($db);
$test = new Test($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$students = $user->getStudentsByTeacher($teacher_id);

// Обработка добавления нового ученика
if ($_POST && isset($_POST['add_student'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    if ($user->createStudent($username, $password, $first_name, $last_name, $teacher_id)) {
        $success = "Ученик успешно добавлен!";
        $students = $user->getStudentsByTeacher($teacher_id); // Обновляем список
    } else {
        $error = "Ошибка при добавлении ученика";
    }
}

// Обработка удаления ученика
if ($_GET && isset($_GET['delete_student'])) {
    $student_id = $_GET['delete_student'];
    if ($user->deleteStudent($student_id, $teacher_id)) {
        $success = "Ученик успешно удален!";
        $students = $user->getStudentsByTeacher($teacher_id);
    } else {
        $error = "Ошибка при удалении ученика";
    }
}

// Обработка сброса прогресса ученика
if ($_POST && isset($_POST['reset_progress'])) {
    $student_id = $_POST['student_id'];
    
    // Сбрасываем прогресс по словам
    $vocabulary_reset = $vocabulary->resetStudentProgress($student_id, $teacher_id);
    
    // Сбрасываем прогресс по тестам
    $tests_reset = $test->resetStudentTestProgress($student_id, $teacher_id);
    
    if ($vocabulary_reset && $tests_reset) {
        $success = "Прогресс ученика успешно сброшен (слова и тесты)!";
    } else {
        $error = "Ошибка при сбросе прогресса";
    }
}

$sortable_fields = [
    'last_name' => 'Фамилия',
    'avg_deck_progress' => 'Прогресс (колоды)',
    'avg_test_score' => 'Прогресс (тесты)',
    'learned_words' => 'Изучено слов',
    'words_to_review' => 'К изучению',
    'deck_count' => 'Количество колод'
];

$sort_by = $_GET['sort_by'] ?? 'last_name';
$sort_order = $_GET['sort_order'] ?? 'asc';

// Сбор данных для сортировки
$students_with_stats = [];
foreach ($students as $student) {
    $student_decks = $deck->getStudentDeckStats($student['id'], $teacher_id);
    $total_words = 0;
    $words_to_review = 0;
    $learned_words = 0;
    foreach ($student_decks as $deck_stat) {
        $total_words += $deck_stat['total_words'];
        $words_to_review += $deck_stat['words_to_review'];
        $learned_words += $deck_stat['learned_words'];
    }
    
    $student['deck_count'] = count($student_decks);
    $student['total_words'] = $total_words;
    $student['words_to_review'] = $words_to_review;
    $student['learned_words'] = $learned_words;
    $student['avg_deck_progress'] = $vocabulary->getStudentAverageDeckProgress($student['id']);
    $student['avg_test_score'] = $test->getStudentAverageTestScore($student['id']);
    $students_with_stats[] = $student;
}

// Сортировка
usort($students_with_stats, function($a, $b) use ($sort_by, $sort_order) {
    $val_a = $a[$sort_by];
    $val_b = $b[$sort_by];

    if ($val_a == $val_b) {
        return 0;
    }

    if ($sort_order === 'asc') {
        return $val_a < $val_b ? -1 : 1;
    } else {
        return $val_a > $val_b ? -1 : 1;
    }
});


$page_title = 'Мои ученики';
$page_icon = '👥';
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

    input[type="text"], input[type="password"] {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e1e1e1;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    input[type="text"]:focus, input[type="password"]:focus {
        outline: none;
        border-color: #667eea;
    }

    .students-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .student-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
        border-left: 5px solid #667eea;
        display: flex;
        flex-direction: column;
    }

    .student-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .student-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }

    .student-avatar {
        font-size: 2.5rem;
        margin-right: 1rem;
    }

    .student-info {
        flex: 1;
    }

    .student-name {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.3rem;
    }

    .student-username {
        color: #667eea;
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }

    .student-date {
        color: #666;
        font-size: 0.8rem;
    }

    .student-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin: 1rem 0 0 0;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .secondary-stats {
        grid-template-columns: repeat(2, 1fr);
        margin-top: 0.5rem;
        background: #f0f2f5;
    }

    .student-stats .stat-item {
        text-align: center;
    }

    .student-stats .stat-number {
        font-size: 1.3rem;
        font-weight: bold;
        color: #667eea;
    }

    .student-stats .stat-label {
        font-size: 0.7rem;
        color: #666;
        margin-top: 0.3rem;
    }

    .assigned-decks {
        margin: 1rem 0;
        flex-grow: 1;
    }

    .decks-label {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .deck-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .deck-tag {
        padding: 0.3rem 0.6rem;
        border-radius: 15px;
        font-size: 0.8rem;
        border: 1px solid;
        display: inline-block;
    }

    .deck-tag small {
        opacity: 0.7;
        margin-left: 0.3rem;
    }

    .no-decks {
        color: #999;
        font-style: italic;
        text-align: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .student-actions {
        margin-top: 1rem;
        text-align: right;
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        align-items: center;
    }

    .sort-controls {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        align-items: center;
        flex-wrap: wrap;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 8px;
    }

    .sort-controls label {
        font-weight: 500;
        margin-bottom: 0;
    }

    .sort-controls .btn {
        background-color: #fff;
        color: #667eea;
        border: 1px solid #667eea;
        padding: 0.4rem 0.8rem;
    }

    .sort-controls .btn.active {
        background-color: #667eea;
        color: #fff;
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
        .form-grid, .students-grid {
            grid-template-columns: 1fr;
        }
        .student-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Добавить нового ученика</h2>
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Имя пользователя:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="first_name">Имя:</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Фамилия:</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            <button type="submit" name="add_student" class="btn btn-primary">Добавить ученика</button>
        </form>
    </div>

    <div class="card">
        <h2>Мои ученики (<?php echo count($students); ?>)</h2>

        <?php if (!empty($students)): ?>
            <div class="sort-controls">
                <label>Сортировать по:</label>
                <?php foreach ($sortable_fields as $field => $label): 
                    $is_active = ($field === $sort_by);
                    $order_for_link = ($is_active && $sort_order === 'desc') ? 'asc' : 'desc';
                    $icon = $is_active ? ($sort_order === 'desc' ? '<i class="fas fa-arrow-down"></i>' : '<i class="fas fa-arrow-up"></i>') : '';
                ?>
                    <a href="?sort_by=<?php echo $field; ?>&sort_order=<?php echo $order_for_link; ?>" 
                       class="btn <?php echo $is_active ? 'active' : ''; ?>">
                        <?php echo $label . ' ' . $icon; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($students)): ?>
            <div class="empty-state">
                <h3>👨‍🎓 Учеников пока нет</h3>
                <p>Добавьте учеников для начала работы с платформой.</p>
            </div>
        <?php else: ?>
            <div class="students-grid">
                <?php foreach ($students_with_stats as $student): 
                    $student_decks = $deck->getStudentDeckStats($student['id'], $teacher_id);
                    // Эти значения уже посчитаны, но для отображения тегов колод нужен этот вызов
                ?>
                <div class="student-card">
                    <div>
                        <div class="student-header">
                            <div class="student-avatar">👨‍🎓</div>
                            <div class="student-info">
                                <div class="student-name"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                <div class="student-username">@<?php echo htmlspecialchars($student['username']); ?></div>
                                <div class="student-date">Регистрация: <?php echo date('d.m.Y', strtotime($student['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="student-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $student['deck_count']; ?></div>
                                <div class="stat-label">Колод</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $student['total_words']; ?></div>
                                <div class="stat-label">Слов</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $student['words_to_review']; ?></div>
                                <div class="stat-label">К изучению</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $student['learned_words']; ?></div>
                                <div class="stat-label">Изучено</div>
                            </div>
                        </div>

                        <div class="student-stats secondary-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo round($student['avg_deck_progress'], 1); ?>%</div>
                                <div class="stat-label">Прогресс по колодам</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo round($student['avg_test_score'], 1); ?>%</div>
                                <div class="stat-label">Средний балл за тесты</div>
                            </div>
                        </div>

                        <?php if (!empty($student_decks)): ?>
                            <div class="assigned-decks">
                                <div class="decks-label">Назначенные колоды:</div>
                                <div class="deck-tags">
                                    <?php foreach ($student_decks as $deck_stat): ?>
                                        <span class="deck-tag" style="background-color: <?php echo htmlspecialchars($deck_stat['color']); ?>20; border-color: <?php echo htmlspecialchars($deck_stat['color']); ?>;">
                                            <?php echo htmlspecialchars($deck_stat['name']); ?>
                                            <small>(<?php echo $deck_stat['total_words']; ?>)</small>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-decks">
                                <em>Колоды не назначены</em>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="student-actions">
                        <a href="edit_student.php?id=<?php echo $student['id']; ?>" 
                           class="btn btn-info" 
                           title="Редактировать данные">✏️</a>
                        <a href="student_progress.php?student_id=<?php echo $student['id']; ?>" 
                           class="btn btn-primary" 
                           title="Управление прогрессом">📊</a>
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Вы уверены, что хотите сбросить весь прогресс обучения этого ученика (включая результаты тестов и изученные слова)? Это действие нельзя отменить.')">
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                            <button type="submit" name="reset_progress" class="btn btn-warning" title="Сбросить прогресс (слова и тесты)">🔄</button>
                        </form>
                        <a href="?delete_student=<?php echo $student['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Вы уверены, что хотите удалить этого ученика?')" 
                           title="Удалить ученика">🗑️</a>
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
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>
</body>
</html>
