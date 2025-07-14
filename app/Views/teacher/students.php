<?php
$page_title = 'Мои ученики';
$page_icon = '👥';
include __DIR__ . '/header.php';
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

    .btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ff4757 0%, #ff3742 100%);
    }

    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        font-weight: 500;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>👤 Добавить нового ученика</h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="username">Логин:</label>
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
            <button type="submit" name="add_student" class="btn">➕ Добавить ученика</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>👥 Список учеников (<?php echo count($students_with_stats); ?>)</h2>
    </div>
    <div class="students-grid">
        <?php foreach ($students_with_stats as $student): ?>
            <div class="student-card">
                <div class="student-header">
                    <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                    <span class="student-username">@<?php echo htmlspecialchars($student['username']); ?></span>
                </div>
                
                <div class="student-stats">
                    <div class="stat-item">
                        <span class="stat-label">Колод:</span>
                        <span class="stat-value"><?php echo $student['deck_count']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Изучено слов:</span>
                        <span class="stat-value"><?php echo $student['learned_words']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">К повторению:</span>
                        <span class="stat-value"><?php echo $student['words_to_review']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Средний балл тестов:</span>
                        <span class="stat-value"><?php echo round($student['avg_test_score'], 1); ?>%</span>
                    </div>
                </div>

                <div class="student-actions">
                    <a href="/teacher/editstudent?id=<?php echo $student['id']; ?>" class="btn btn-sm">✏️ Редактировать</a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                        <button type="submit" name="reset_progress" class="btn btn-warning btn-sm" 
                                onclick="return confirm('Сбросить весь прогресс ученика?')">🔄 Сбросить прогресс</button>
                    </form>
                    <a href="?delete_student=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Удалить ученика?')">🗑️ Удалить</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
