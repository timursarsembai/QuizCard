<?php
$page_title = "Карточки для изучения";
$page_scripts = ['/js/audio-player.js'];

// Создадим простой header для студентов
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - QuizCard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/security.css">
    
    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
</head>
<body>
    <header class="navbar" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="/student/dashboard" class="navbar-brand">
                    <i class="fas fa-graduation-cap"></i>
                    QuizCard Student
                </a>
                
                <nav class="navbar-nav d-flex">
                    <a href="/student/dashboard" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span class="d-none d-md-inline">Главная</span>
                    </a>
                    <a href="/student/flashcards" class="nav-link">
                        <i class="fas fa-layer-group"></i>
                        <span class="d-none d-md-inline">Карточки</span>
                    </a>
                    <a href="/student/tests" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="d-none d-md-inline">Тесты</span>
                    </a>
                    <a href="/student/statistics" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span class="d-none d-md-inline">Статистика</span>
                    </a>
                    <a href="/logout" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-none d-md-inline">Выход</span>
                    </a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container mt-4">

<style>
.flashcard-container {
    max-width: 600px;
    margin: 0 auto;
    perspective: 1000px;
}

.flashcard {
    width: 100%;
    height: 400px;
    position: relative;
    transform-style: preserve-3d;
    transition: transform 0.6s;
    cursor: pointer;
}

.flashcard.flipped {
    transform: rotateY(180deg);
}

.flashcard-side {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow-lg);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 2rem;
    text-align: center;
}

.flashcard-front {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.flashcard-back {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
    color: white;
    transform: rotateY(180deg);
}

.word-text {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.word-translation {
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.word-image {
    max-width: 200px;
    max-height: 150px;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.audio-controls {
    margin-top: 1rem;
}

.audio-btn {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.audio-btn:hover {
    background: rgba(255,255,255,0.3);
    border-color: rgba(255,255,255,0.5);
}

.controls-panel {
    max-width: 600px;
    margin: 2rem auto;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
}

.difficulty-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.difficulty-btn {
    padding: 1rem;
    border: none;
    border-radius: var(--border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.difficulty-easy {
    background: #d4edda;
    color: #155724;
}

.difficulty-easy:hover {
    background: #c3e6cb;
}

.difficulty-medium {
    background: #fff3cd;
    color: #856404;
}

.difficulty-medium:hover {
    background: #ffeaa7;
}

.difficulty-hard {
    background: #f8d7da;
    color: #721c24;
}

.difficulty-hard:hover {
    background: #f5c6cb;
}

.deck-selector {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.mode-selector {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.mode-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #ced4da;
    background: white;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: all 0.2s ease;
}

.mode-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.progress-info {
    background: var(--light-color);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 1rem;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    text-align: center;
}

.progress-stat {
    background: white;
    padding: 1rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary-color);
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
    .word-text {
        font-size: 2rem;
    }
    
    .word-translation {
        font-size: 1.5rem;
    }
    
    .flashcard {
        height: 350px;
    }
    
    .difficulty-buttons {
        grid-template-columns: 1fr;
    }
    
    .mode-selector {
        flex-direction: column;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-layer-group"></i> Карточки для изучения</h1>
    <a href="/student/dashboard" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i>
        Назад
    </a>
</div>

<!-- Выбор колоды и режима -->
<div class="deck-selector">
    <h5 class="mb-3">
        <i class="fas fa-cog"></i>
        Настройки изучения
    </h5>
    
    <div class="row">
        <div class="col-md-6">
            <label for="deckSelect" class="form-label">Выберите колоду:</label>
            <select class="form-select" id="deckSelect">
                <option value="">Все колоды</option>
                <?php if (!empty($available_decks)): ?>
                    <?php foreach ($available_decks as $deck): ?>
                        <option value="<?php echo $deck['id']; ?>" 
                                <?php echo ($deck_id == $deck['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($deck['name']); ?>
                            (<?php echo $deck['words_count']; ?> слов)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="col-md-6">
            <label class="form-label">Режим изучения:</label>
            <div class="mode-selector">
                <button class="mode-btn active" data-mode="normal">
                    <i class="fas fa-play"></i>
                    Новые слова
                </button>
                <button class="mode-btn" data-mode="today">
                    <i class="fas fa-calendar-day"></i>
                    Сегодня изученные
                </button>
                <button class="mode-btn" data-mode="all_studied">
                    <i class="fas fa-history"></i>
                    Все изученные
                </button>
            </div>
        </div>
    </div>
    
    <?php if ($deck_info): ?>
        <div class="progress-info mt-3">
            <h6>Колода: <?php echo htmlspecialchars($deck_info['name']); ?></h6>
            <div class="progress-stats">
                <div class="progress-stat">
                    <div class="stat-number"><?php echo $deck_info['total_words'] ?? 0; ?></div>
                    <div class="text-muted">Всего слов</div>
                </div>
                <div class="progress-stat">
                    <div class="stat-number"><?php echo $deck_info['studied_words'] ?? 0; ?></div>
                    <div class="text-muted">Изучено</div>
                </div>
                <div class="progress-stat">
                    <div class="stat-number"><?php echo $deck_info['daily_limit'] ?? 20; ?></div>
                    <div class="text-muted">Лимит/день</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Карточка для изучения -->
<?php if (!empty($words_for_review)): ?>
    <div class="flashcard-container">
        <div class="flashcard" id="flashcard" onclick="flipCard()">
            <div class="flashcard-side flashcard-front" id="cardFront">
                <div class="word-text" id="wordText"><?php echo htmlspecialchars($words_for_review[0]['foreign_word']); ?></div>
                <?php if (!empty($words_for_review[0]['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($words_for_review[0]['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($words_for_review[0]['foreign_word']); ?>" 
                         class="word-image" id="wordImage">
                <?php endif; ?>
                <?php if (!empty($words_for_review[0]['audio_path'])): ?>
                    <div class="audio-controls">
                        <button class="audio-btn audio-play-btn" 
                                data-audio-path="<?php echo htmlspecialchars($words_for_review[0]['audio_path']); ?>"
                                data-word-id="<?php echo $words_for_review[0]['id']; ?>">
                            <i class="fas fa-volume-up"></i>
                            Произношение
                        </button>
                    </div>
                <?php endif; ?>
                <div class="mt-3">
                    <small class="text-white-50">
                        <i class="fas fa-mouse-pointer"></i>
                        Нажмите, чтобы увидеть перевод
                    </small>
                </div>
            </div>
            
            <div class="flashcard-side flashcard-back" id="cardBack">
                <div class="word-translation" id="wordTranslation"><?php echo htmlspecialchars($words_for_review[0]['translation']); ?></div>
                <div class="mt-3">
                    <small class="text-white-50">
                        <i class="fas fa-lightbulb"></i>
                        Оцените, насколько хорошо вы знаете это слово
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Панель управления -->
    <div class="controls-panel">
        <h6 class="mb-3">
            <i class="fas fa-star"></i>
            Насколько хорошо вы знаете это слово?
        </h6>
        
        <div class="difficulty-buttons">
            <button class="difficulty-btn difficulty-hard" onclick="rateWord('hard')">
                <i class="fas fa-times-circle"></i>
                <div>Не знаю</div>
                <small>Покажется снова скоро</small>
            </button>
            <button class="difficulty-btn difficulty-medium" onclick="rateWord('medium')">
                <i class="fas fa-exclamation-circle"></i>
                <div>Знаю плохо</div>
                <small>Повторю через день</small>
            </button>
            <button class="difficulty-btn difficulty-easy" onclick="rateWord('easy')">
                <i class="fas fa-check-circle"></i>
                <div>Знаю хорошо</div>
                <small>Повторю через неделю</small>
            </button>
        </div>
        
        <div class="text-center mt-3">
            <button class="btn btn-outline-primary" onclick="nextCard()">
                <i class="fas fa-arrow-right"></i>
                Пропустить
            </button>
        </div>
    </div>
    
    <!-- Прогресс -->
    <div class="text-center">
        <small class="text-muted">
            Карточка <span id="currentCardIndex">1</span> из <span id="totalCards"><?php echo count($words_for_review); ?></span>
        </small>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-graduation-cap"></i>
        <h4>Нет слов для изучения</h4>
        <p>
            <?php if ($review_mode === 'today'): ?>
                Сегодня вы еще не изучили ни одного слова.
            <?php elseif ($review_mode === 'all_studied'): ?>
                У вас нет изученных слов для повторения.
            <?php else: ?>
                На сегодня все слова изучены или нет доступных колод.
            <?php endif; ?>
        </p>
        <a href="/student/dashboard" class="btn btn-primary">
            <i class="fas fa-home"></i>
            Вернуться на главную
        </a>
    </div>
<?php endif; ?>

<script>
let currentWordIndex = 0;
let wordsData = <?php echo json_encode($words_for_review ?? []); ?>;
let isFlipped = false;

// Переключение карточки
function flipCard() {
    const flashcard = document.getElementById('flashcard');
    flashcard.classList.toggle('flipped');
    isFlipped = !isFlipped;
}

// Оценка слова
function rateWord(difficulty) {
    if (!isFlipped) {
        flipCard();
        return;
    }
    
    const currentWord = wordsData[currentWordIndex];
    
    // Отправляем AJAX запрос для обновления прогресса
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_progress&vocabulary_id=${currentWord.id}&difficulty=${difficulty}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            nextCard();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        nextCard(); // Продолжаем даже при ошибке
    });
}

// Следующая карточка
function nextCard() {
    currentWordIndex++;
    
    if (currentWordIndex >= wordsData.length) {
        showCompletionMessage();
        return;
    }
    
    updateCard();
    
    // Сбрасываем состояние карточки
    if (isFlipped) {
        flipCard();
    }
}

// Обновление карточки
function updateCard() {
    const currentWord = wordsData[currentWordIndex];
    
    document.getElementById('wordText').textContent = currentWord.foreign_word;
    document.getElementById('wordTranslation').textContent = currentWord.translation;
    document.getElementById('currentCardIndex').textContent = currentWordIndex + 1;
    
    // Обновляем изображение
    const imageElement = document.getElementById('wordImage');
    if (currentWord.image_path) {
        if (imageElement) {
            imageElement.src = currentWord.image_path;
            imageElement.style.display = 'block';
        } else {
            // Создаем новое изображение
            const img = document.createElement('img');
            img.id = 'wordImage';
            img.className = 'word-image';
            img.src = currentWord.image_path;
            img.alt = currentWord.foreign_word;
            document.getElementById('cardFront').insertBefore(img, document.querySelector('.audio-controls') || document.querySelector('.mt-3'));
        }
    } else {
        if (imageElement) {
            imageElement.style.display = 'none';
        }
    }
    
    // Обновляем аудио
    const audioBtn = document.querySelector('.audio-play-btn');
    if (currentWord.audio_path) {
        if (audioBtn) {
            audioBtn.dataset.audioPath = currentWord.audio_path;
            audioBtn.dataset.wordId = currentWord.id;
            audioBtn.style.display = 'block';
        }
    } else {
        if (audioBtn) {
            audioBtn.style.display = 'none';
        }
    }
}

// Сообщение о завершении
function showCompletionMessage() {
    document.querySelector('.flashcard-container').innerHTML = `
        <div class="empty-state">
            <i class="fas fa-trophy" style="color: #ffd700;"></i>
            <h4>Поздравляем! Все карточки изучены!</h4>
            <p>Вы завершили изучение всех доступных слов в этом режиме.</p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i>
                    Начать заново
                </button>
                <a href="/student/dashboard" class="btn btn-success">
                    <i class="fas fa-home"></i>
                    На главную
                </a>
            </div>
        </div>
    `;
    
    document.querySelector('.controls-panel').style.display = 'none';
}

// Обработчики режимов изучения
document.querySelectorAll('.mode-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const mode = this.dataset.mode;
        const deckId = document.getElementById('deckSelect').value;
        
        window.location.href = `/student/flashcards?deck_id=${deckId}&review_mode=${mode}`;
    });
});

// Обработчик выбора колоды
document.getElementById('deckSelect').addEventListener('change', function() {
    const deckId = this.value;
    const mode = document.querySelector('.mode-btn.active').dataset.mode;
    
    window.location.href = `/student/flashcards?deck_id=${deckId}&review_mode=${mode}`;
});

// Горячие клавиши
document.addEventListener('keydown', function(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
    
    switch(e.key) {
        case ' ':
        case 'Enter':
            e.preventDefault();
            flipCard();
            break;
        case '1':
            e.preventDefault();
            rateWord('hard');
            break;
        case '2':
            e.preventDefault();
            rateWord('medium');
            break;
        case '3':
            e.preventDefault();
            rateWord('easy');
            break;
        case 'ArrowRight':
        case 'n':
            e.preventDefault();
            nextCard();
            break;
    }
});
</script>

<!-- JavaScript -->
<script src="/js/security.js"></script>
<script src="/js/audio-player.js"></script>

</main>
</body>
</html>
