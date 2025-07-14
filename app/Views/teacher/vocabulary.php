<?php
$page_title = "Управление словарём";
$page_scripts = ['/js/audio-upload.js'];
$inline_scripts = '';

include __DIR__ . '/header.php';
?>

<style>
.vocabulary-controls {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.word-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.word-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.word-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--box-shadow-lg);
}

.word-card-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 1rem 1.5rem;
    position: relative;
}

.word-card-body {
    padding: 1.5rem;
}

.word-foreign {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0 0 0.5rem 0;
}

.word-translation {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.word-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: var(--secondary-color);
}

.word-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.word-audio {
    margin-bottom: 1rem;
}

.word-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: space-between;
}

.add-word-form {
    background: var(--light-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.file-upload-group {
    border: 2px dashed #ced4da;
    border-radius: var(--border-radius);
    padding: 1rem;
    text-align: center;
    transition: border-color 0.2s ease;
}

.file-upload-group:hover {
    border-color: var(--primary-color);
}

.file-upload-group.drag-over {
    border-color: var(--primary-color);
    background-color: rgba(0, 123, 255, 0.05);
}

.deck-header-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 2rem;
    margin-bottom: 2rem;
}

.deck-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.deck-color-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.deck-details h2 {
    margin: 0 0 0.5rem 0;
    color: var(--dark-color);
}

.deck-description {
    color: var(--secondary-color);
    margin: 0;
}

.search-filter-bar {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.search-filter-bar input,
.search-filter-bar select {
    min-width: 200px;
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
    .word-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .search-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-filter-bar input,
    .search-filter-bar select {
        min-width: unset;
    }
    
    .deck-info {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<!-- Информация о колоде -->
<div class="deck-header-section">
    <div class="deck-info">
        <div class="deck-color-circle" style="background-color: <?php echo htmlspecialchars($current_deck['color']); ?>;">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="deck-details">
            <h2><?php echo htmlspecialchars($current_deck['name']); ?></h2>
            <p class="deck-description">
                <?php echo htmlspecialchars($current_deck['description'] ?: 'Без описания'); ?>
            </p>
            <small class="text-muted">
                <i class="fas fa-clock"></i>
                Лимит: <?php echo $current_deck['daily_word_limit']; ?> слов/день •
                <i class="fas fa-spell-check"></i>
                Всего слов: <?php echo count($words); ?>
            </small>
        </div>
        <div class="ms-auto">
            <a href="/teacher/decks" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i>
                К списку колод
            </a>
        </div>
    </div>
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

<!-- Элементы управления -->
<div class="vocabulary-controls">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <i class="fas fa-book"></i>
            Словарь колоды
        </h4>
        <div class="d-flex gap-2">
            <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addWordForm">
                <i class="fas fa-plus"></i>
                Добавить слово
            </button>
            <a href="/teacher/import-words?deck_id=<?php echo $deck_id; ?>" class="btn btn-info">
                <i class="fas fa-upload"></i>
                Импорт из файла
            </a>
        </div>
    </div>
    
    <div class="search-filter-bar">
        <div class="input-group">
            <span class="input-group-text">
                <i class="fas fa-search"></i>
            </span>
            <input type="text" class="form-control" id="searchWords" placeholder="Поиск слов...">
        </div>
        
        <select class="form-select" id="sortBy">
            <option value="created_desc">Сначала новые</option>
            <option value="created_asc">Сначала старые</option>
            <option value="foreign_asc">А-Я (иностранное)</option>
            <option value="foreign_desc">Я-А (иностранное)</option>
            <option value="translation_asc">А-Я (перевод)</option>
            <option value="translation_desc">Я-А (перевод)</option>
        </select>
        
        <select class="form-select" id="filterBy">
            <option value="all">Все слова</option>
            <option value="with_audio">С аудио</option>
            <option value="with_image">С изображением</option>
            <option value="without_audio">Без аудио</option>
            <option value="without_image">Без изображения</option>
        </select>
    </div>
</div>

<!-- Форма добавления слова -->
<div class="collapse" id="addWordForm">
    <div class="add-word-form">
        <h5 class="mb-3">
            <i class="fas fa-plus-circle"></i>
            Добавить новое слово
        </h5>
        
        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
            <input type="hidden" name="add_word" value="1">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="foreign_word" class="form-label">Иностранное слово *</label>
                    <input type="text" class="form-control" id="foreign_word" name="foreign_word" required
                           placeholder="Например: apple">
                    <div class="invalid-feedback">Пожалуйста, введите иностранное слово.</div>
                </div>
                
                <div class="form-group">
                    <label for="translation" class="form-label">Перевод *</label>
                    <input type="text" class="form-control" id="translation" name="translation" required
                           placeholder="Например: яблоко">
                    <div class="invalid-feedback">Пожалуйста, введите перевод.</div>
                </div>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="word_image" class="form-label">Изображение</label>
                    <div class="file-upload-group audio-upload-container">
                        <input type="file" class="form-control" id="word_image" name="image" 
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="mt-2">
                            <i class="fas fa-image fa-2x text-muted"></i>
                            <p class="mb-0">JPG, PNG, GIF, WebP • Максимум 2MB</p>
                        </div>
                        <div class="image-preview-container mt-2" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="word_audio" class="form-label">Аудио произношение</label>
                    <div class="file-upload-group audio-upload-container">
                        <input type="file" class="form-control" id="word_audio" name="audio" 
                               accept="audio/mp3,audio/wav,audio/ogg,audio/mpeg">
                        <div class="mt-2">
                            <i class="fas fa-microphone fa-2x text-muted"></i>
                            <p class="mb-0">MP3, WAV, OGG • Максимум 3MB • До 30 сек</p>
                        </div>
                        <div class="audio-preview-container mt-2" style="display: none;"></div>
                        <div class="audio-error-container mt-2" style="display: none;"></div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i>
                    Добавить слово
                </button>
                <button type="button" class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#addWordForm">
                    <i class="fas fa-times"></i>
                    Отмена
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Список слов -->
<?php if (!empty($words)): ?>
    <div class="word-grid" id="wordGrid">
        <?php foreach ($words as $word): ?>
            <div class="word-card" data-foreign="<?php echo strtolower(htmlspecialchars($word['foreign_word'])); ?>" 
                 data-translation="<?php echo strtolower(htmlspecialchars($word['translation'])); ?>"
                 data-has-audio="<?php echo !empty($word['audio_path']) ? 'true' : 'false'; ?>"
                 data-has-image="<?php echo !empty($word['image_path']) ? 'true' : 'false'; ?>"
                 data-created="<?php echo strtotime($word['created_at']); ?>">
                
                <div class="word-card-header">
                    <h5 class="word-foreign"><?php echo htmlspecialchars($word['foreign_word']); ?></h5>
                    <p class="word-translation"><?php echo htmlspecialchars($word['translation']); ?></p>
                </div>
                
                <div class="word-card-body">
                    <div class="word-meta">
                        <span>
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d.m.Y', strtotime($word['created_at'])); ?>
                        </span>
                        <span>
                            ID: <?php echo $word['id']; ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($word['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($word['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($word['foreign_word']); ?>" 
                             class="word-image">
                    <?php endif; ?>
                    
                    <?php if (!empty($word['audio_path'])): ?>
                        <div class="word-audio">
                            <button type="button" class="btn btn-sm btn-outline-primary audio-play-btn w-100" 
                                    data-audio-path="<?php echo htmlspecialchars($word['audio_path']); ?>"
                                    data-word-id="<?php echo $word['id']; ?>">
                                <i class="fas fa-play"></i>
                                Прослушать произношение
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="word-actions">
                        <button class="btn btn-sm btn-outline-secondary" 
                                onclick="editWord(<?php echo $word['id']; ?>)">
                            <i class="fas fa-edit"></i>
                            <span class="d-none d-sm-inline">Редактировать</span>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteWord(<?php echo $word['id']; ?>, '<?php echo htmlspecialchars($word['foreign_word'], ENT_QUOTES); ?>')">
                            <i class="fas fa-trash"></i>
                            <span class="d-none d-sm-inline">Удалить</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-book-open"></i>
        <h4>В этой колоде пока нет слов</h4>
        <p>Добавьте первое слово, чтобы начать создание словаря.</p>
        <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#addWordForm">
            <i class="fas fa-plus"></i>
            Добавить первое слово
        </button>
    </div>
<?php endif; ?>

<script>
// Поиск и фильтрация слов
document.getElementById('searchWords').addEventListener('input', filterWords);
document.getElementById('sortBy').addEventListener('change', filterWords);
document.getElementById('filterBy').addEventListener('change', filterWords);

function filterWords() {
    const searchTerm = document.getElementById('searchWords').value.toLowerCase();
    const sortBy = document.getElementById('sortBy').value;
    const filterBy = document.getElementById('filterBy').value;
    const wordCards = Array.from(document.querySelectorAll('.word-card'));
    
    // Фильтрация
    wordCards.forEach(card => {
        const foreign = card.dataset.foreign;
        const translation = card.dataset.translation;
        const hasAudio = card.dataset.hasAudio === 'true';
        const hasImage = card.dataset.hasImage === 'true';
        
        let showCard = true;
        
        // Поиск
        if (searchTerm && !foreign.includes(searchTerm) && !translation.includes(searchTerm)) {
            showCard = false;
        }
        
        // Фильтр
        switch (filterBy) {
            case 'with_audio':
                if (!hasAudio) showCard = false;
                break;
            case 'with_image':
                if (!hasImage) showCard = false;
                break;
            case 'without_audio':
                if (hasAudio) showCard = false;
                break;
            case 'without_image':
                if (hasImage) showCard = false;
                break;
        }
        
        card.style.display = showCard ? 'block' : 'none';
    });
    
    // Сортировка
    const visibleCards = wordCards.filter(card => card.style.display !== 'none');
    visibleCards.sort((a, b) => {
        switch (sortBy) {
            case 'created_asc':
                return parseInt(a.dataset.created) - parseInt(b.dataset.created);
            case 'created_desc':
                return parseInt(b.dataset.created) - parseInt(a.dataset.created);
            case 'foreign_asc':
                return a.dataset.foreign.localeCompare(b.dataset.foreign);
            case 'foreign_desc':
                return b.dataset.foreign.localeCompare(a.dataset.foreign);
            case 'translation_asc':
                return a.dataset.translation.localeCompare(b.dataset.translation);
            case 'translation_desc':
                return b.dataset.translation.localeCompare(a.dataset.translation);
            default:
                return 0;
        }
    });
    
    // Перестраиваем DOM
    const container = document.getElementById('wordGrid');
    visibleCards.forEach(card => container.appendChild(card));
}

// Функции управления словами
function editWord(wordId) {
    // Здесь можно открыть модальное окно редактирования
    // Или перенаправить на страницу редактирования
    window.location.href = `/teacher/vocabulary/edit?word_id=${wordId}&deck_id=<?php echo $deck_id; ?>`;
}

function deleteWord(wordId, wordText) {
    if (confirm(`Вы уверены, что хотите удалить слово "${wordText}"?`)) {
        window.location.href = `/teacher/vocabulary?deck_id=<?php echo $deck_id; ?>&delete_word=${wordId}`;
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
