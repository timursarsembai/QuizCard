<?php
$page_title = 'Управление тестами';
$page_icon = '📝';
include __DIR__ . '/header.php';
?>

<style>
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
        border-left: 5px solid #667eea;
    }

    .test-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

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

    input, select {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e1e1e1;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    input:focus, select:focus {
        outline: none;
        border-color: #667eea;
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
        <h2>➕ Создать новый тест</h2>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label for="deck_id">Выберите колоду:</label>
                    <select id="deck_id" name="deck_id" required>
                        <option value="">Выберите колоду...</option>
                        <?php foreach ($decks as $deck): ?>
                            <option value="<?php echo $deck['id']; ?>">
                                <?php echo htmlspecialchars($deck['name']); ?> 
                                (<?php echo $deck['word_count']; ?> слов)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="test_name">Название теста:</label>
                    <input type="text" id="test_name" name="test_name" required placeholder="Введите название теста">
                </div>
                <div class="form-group">
                    <label for="questions_count">Количество вопросов:</label>
                    <input type="number" id="questions_count" name="questions_count" value="10" min="1" max="50">
                </div>
                <div class="form-group">
                    <label for="time_limit">Ограничение времени (минуты):</label>
                    <input type="number" id="time_limit" name="time_limit" placeholder="Без ограничения" min="1">
                </div>
            </div>
            <button type="submit" name="create_test" class="btn">➕ Создать тест</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>📝 Мои тесты (<?php echo $total_tests; ?>)</h2>
    </div>
    
    <?php if (empty($all_tests)): ?>
        <div class="card-body">
            <p>У вас пока нет созданных тестов.</p>
        </div>
    <?php else: ?>
        <div class="tests-grid">
            <?php foreach ($all_tests as $test): ?>
                <div class="test-card">
                    <h3><?php echo htmlspecialchars($test['name']); ?></h3>
                    <p><strong>Колода:</strong> <?php echo htmlspecialchars($test['deck_name']); ?></p>
                    <p><strong>Вопросов:</strong> <?php echo $test['questions_count']; ?></p>
                    <p><strong>Время:</strong> 
                        <?php echo $test['time_limit'] ? $test['time_limit'] . ' мин' : 'Без ограничения'; ?>
                    </p>
                    <p><strong>Прохождений:</strong> <?php echo $test['attempts_count'] ?? 0; ?></p>
                    
                    <div class="test-actions" style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="/teacher/testedit?test_id=<?php echo $test['id']; ?>" class="btn btn-sm">✏️ Редактировать</a>
                        <a href="/teacher/testpreview?test_id=<?php echo $test['id']; ?>" class="btn btn-sm">👀 Предпросмотр</a>
                        <a href="/teacher/testresults?test_id=<?php echo $test['id']; ?>" class="btn btn-sm">📊 Результаты</a>
                        <a href="?delete_test=<?php echo $test['id']; ?>" class="btn btn-danger btn-sm" 
                           onclick="return confirm('Удалить тест?')">🗑️ Удалить</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>
