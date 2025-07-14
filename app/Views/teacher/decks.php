<?php
$page_title = "Управление колодами";
$page_scripts = [];
$inline_scripts = '';

include __DIR__ . '/header.php';
?>

<style>
.deck-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.deck-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.deck-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--box-shadow-lg);
}

.deck-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--primary-color), #5a67d8);
    color: white;
    position: relative;
}

.deck-color-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.deck-title {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.deck-description {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.deck-body {
    padding: 1.5rem;
}

.deck-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.deck-stat {
    text-align: center;
    padding: 0.75rem;
    background: var(--light-color);
    border-radius: var(--border-radius);
}

.deck-stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
    display: block;
}

.deck-stat-label {
    font-size: 0.875rem;
    color: var(--secondary-color);
}

.deck-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.deck-actions .btn {
    flex: 1;
    min-width: 0;
}

.create-deck-form {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.color-picker-group {
    display: flex;
    align-items: end;
    gap: 0.5rem;
}

.color-picker {
    width: 50px;
    height: 38px;
    border: 1px solid #ced4da;
    border-radius: var(--border-radius);
    cursor: pointer;
}

.color-presets {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.color-preset {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.color-preset:hover,
.color-preset.active {
    border-color: #333;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--secondary-color);
}

.empty-state i {
    font-size: 4rem;
    color: var(--info-color);
    margin-bottom: 1rem;
}

@media (max-width: 767.98px) {
    .deck-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .deck-stats {
        grid-template-columns: 1fr;
    }
    
    .deck-actions {
        flex-direction: column;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-layer-group"></i> Управление колодами</h1>
    <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createDeckForm">
        <i class="fas fa-plus"></i>
        Создать колоду
    </button>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Форма создания колоды -->
<div class="collapse" id="createDeckForm">
    <div class="create-deck-form">
        <h4 class="mb-3">
            <i class="fas fa-plus-circle"></i>
            Создать новую колоду
        </h4>
        
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="create_deck" value="1">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="deck_name" class="form-label">Название колоды *</label>
                    <input type="text" class="form-control" id="deck_name" name="name" required
                           placeholder="Например: Английский для начинающих">
                    <div class="invalid-feedback">Пожалуйста, введите название колоды.</div>
                </div>
                
                <div class="form-group">
                    <label for="daily_word_limit" class="form-label">Лимит слов в день</label>
                    <input type="number" class="form-control" id="daily_word_limit" name="daily_word_limit" 
                           min="1" max="100" value="20">
                    <div class="form-text">Максимальное количество новых слов для изучения в день</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="deck_description" class="form-label">Описание</label>
                <textarea class="form-control" id="deck_description" name="description" rows="3"
                          placeholder="Краткое описание содержимого колоды..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="deck_color" class="form-label">Цвет колоды</label>
                <div class="color-picker-group">
                    <input type="color" class="color-picker" id="deck_color" name="color" value="#667eea">
                    <span class="form-text">Выберите цвет для идентификации колоды</span>
                </div>
                <div class="color-presets">
                    <div class="color-preset" style="background-color: #667eea;" data-color="#667eea"></div>
                    <div class="color-preset" style="background-color: #764ba2;" data-color="#764ba2"></div>
                    <div class="color-preset" style="background-color: #f093fb;" data-color="#f093fb"></div>
                    <div class="color-preset" style="background-color: #4facfe;" data-color="#4facfe"></div>
                    <div class="color-preset" style="background-color: #43e97b;" data-color="#43e97b"></div>
                    <div class="color-preset" style="background-color: #fa709a;" data-color="#fa709a"></div>
                    <div class="color-preset" style="background-color: #ffecd2;" data-color="#ffecd2"></div>
                    <div class="color-preset" style="background-color: #a8edea;" data-color="#a8edea"></div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Создать колоду
                </button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#createDeckForm">
                    <i class="fas fa-times"></i>
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Список колод -->
<?php if (!empty($decks)): ?>
    <div class="deck-grid">
        <?php foreach ($decks as $deck_item): ?>
            <div class="deck-card">
                <div class="deck-header" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($deck_item['color']); ?>, <?php echo htmlspecialchars($deck_item['color']); ?>88);">
                    <div class="deck-color-indicator" style="background-color: <?php echo htmlspecialchars($deck_item['color']); ?>;"></div>
                    <h5 class="deck-title"><?php echo htmlspecialchars($deck_item['name']); ?></h5>
                    <p class="deck-description"><?php echo htmlspecialchars($deck_item['description'] ?: 'Без описания'); ?></p>
                </div>
                
                <div class="deck-body">
                    <div class="deck-stats">
                        <div class="deck-stat">
                            <span class="deck-stat-number"><?php echo $deck_item['words_count'] ?? 0; ?></span>
                            <span class="deck-stat-label">Слов</span>
                        </div>
                        <div class="deck-stat">
                            <span class="deck-stat-number"><?php echo $deck_item['students_count'] ?? 0; ?></span>
                            <span class="deck-stat-label">Студентов</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            Лимит: <?php echo $deck_item['daily_word_limit']; ?> слов/день
                        </small>
                    </div>
                    
                    <div class="deck-actions">
                        <a href="/teacher/vocabulary?deck_id=<?php echo $deck_item['id']; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-book"></i>
                            <span class="d-none d-sm-inline">Слова</span>
                        </a>
                        <a href="/teacher/deck-students?deck_id=<?php echo $deck_item['id']; ?>" 
                           class="btn btn-sm btn-outline-info">
                            <i class="fas fa-users"></i>
                            <span class="d-none d-sm-inline">Студенты</span>
                        </a>
                        <a href="/teacher/test-manager?deck_id=<?php echo $deck_item['id']; ?>" 
                           class="btn btn-sm btn-outline-success">
                            <i class="fas fa-clipboard-list"></i>
                            <span class="d-none d-sm-inline">Тесты</span>
                        </a>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteDeck(<?php echo $deck_item['id']; ?>, '<?php echo htmlspecialchars($deck_item['name'], ENT_QUOTES); ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-layer-group"></i>
        <h4>У вас пока нет колод</h4>
        <p>Создайте первую колоду, чтобы начать добавлять слова и обучать студентов.</p>
        <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createDeckForm">
            <i class="fas fa-plus"></i>
            Создать первую колоду
        </button>
    </div>
<?php endif; ?>

<script>
// Обработка цветовых пресетов
document.querySelectorAll('.color-preset').forEach(preset => {
    preset.addEventListener('click', function() {
        const color = this.dataset.color;
        document.getElementById('deck_color').value = color;
        
        // Убираем активный класс у всех и добавляем к текущему
        document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
    });
});

// Синхронизация color picker с пресетами
document.getElementById('deck_color').addEventListener('change', function() {
    const selectedColor = this.value;
    document.querySelectorAll('.color-preset').forEach(preset => {
        if (preset.dataset.color === selectedColor) {
            preset.classList.add('active');
        } else {
            preset.classList.remove('active');
        }
    });
});

// Функция удаления колоды
function deleteDeck(deckId, deckName) {
    if (confirm(`Вы уверены, что хотите удалить колоду "${deckName}"?\n\nВсе слова и связанные данные будут удалены безвозвратно.`)) {
        window.location.href = `/teacher/decks?delete_deck=${deckId}`;
    }
}

// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        const forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
