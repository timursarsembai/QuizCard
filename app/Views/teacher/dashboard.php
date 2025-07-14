<?php
$page_title = "Панель управления";
$page_scripts = []; // Дополнительные скрипты для этой страницы
$inline_scripts = ''; // Встроенные скрипты

include __DIR__ . '/header.php';
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
    color: var(--primary-color);
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
    color: var(--primary-color);
}

.activity-item .timestamp {
    font-size: 0.85rem;
    color: #888;
    margin-left: 1rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--secondary-color);
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: var(--border-radius);
    text-decoration: none;
    color: #495057;
    transition: all 0.2s ease;
}

.quick-action-btn:hover {
    background: var(--light-color);
    border-color: var(--primary-color);
    color: var(--primary-color);
    text-decoration: none;
}

.quick-action-btn i {
    font-size: 1.25rem;
    color: var(--primary-color);
}

@media (max-width: 767.98px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .activity-item .timestamp {
        margin-left: 0;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-tachometer-alt"></i> Панель управления</h1>
    <div class="language-switcher">
        <?php include __DIR__ . '/language_switcher.php'; ?>
    </div>
</div>

<!-- Статистические карточки -->
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

<!-- Быстрые действия -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-bolt"></i>
            Быстрые действия
        </h5>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="/teacher/students" class="quick-action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Добавить студента</span>
            </a>
            <a href="/teacher/decks" class="quick-action-btn">
                <i class="fas fa-layer-group"></i>
                <span>Создать колоду</span>
            </a>
            <a href="/teacher/vocabulary" class="quick-action-btn">
                <i class="fas fa-plus"></i>
                <span>Добавить слова</span>
            </a>
            <a href="/teacher/tests" class="quick-action-btn">
                <i class="fas fa-clipboard-list"></i>
                <span>Создать тест</span>
            </a>
            <a href="/teacher/test-results" class="quick-action-btn">
                <i class="fas fa-chart-bar"></i>
                <span>Посмотреть результаты</span>
            </a>
            <a href="/teacher/import-words" class="quick-action-btn">
                <i class="fas fa-upload"></i>
                <span>Импорт слов</span>
            </a>
        </div>
    </div>
</div>

<!-- Последние действия учеников -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-history"></i>
            <span data-translate-key="recent_student_activity">Последние действия учеников</span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($recent_activities)): ?>
            <ul class="activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                    <li class="activity-item">
                        <div>
                            <span class="student-info"><?php echo htmlspecialchars($activity['student_name']); ?></span>
                            <span class="test-info">
                                <span data-translate-key="activity_took_test">прошел(а) тест</span> 
                                "<?php echo htmlspecialchars($activity['test_name']); ?>"
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="score">
                                <span data-translate-key="score">Результат</span>: <?php echo round($activity['score'], 1); ?>%
                            </span>
                            <span class="timestamp"><?php echo date('d.m.Y H:i', strtotime($activity['completed_at'])); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="text-center mt-3">
                <a href="/teacher/test-results" class="btn btn-outline-primary">
                    <i class="fas fa-eye"></i>
                    Посмотреть все результаты
                </a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-info-circle mb-3" style="font-size: 3rem; color: var(--info-color);"></i>
                <p data-translate-key="no_student_activity">Пока нет никаких действий от учеников.</p>
                <p class="text-muted">
                    Добавьте студентов и создайте тесты, чтобы увидеть их активность здесь.
                </p>
                <a href="/teacher/students" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Добавить первого студента
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
