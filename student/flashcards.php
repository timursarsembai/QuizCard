<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Vocabulary.php';
require_once '../classes/Deck.php';
require_once '../includes/init_language.php';
require_once '../includes/translations.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$vocabulary = new Vocabulary($db);
$deck_class = new Deck($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$deck_id = isset($_GET['deck_id']) ? (int)$_GET['deck_id'] : null;
$review_mode = isset($_GET['review_mode']) ? $_GET['review_mode'] : 'normal';

// Получаем информацию о колоде, если выбрана конкретная
$deck_info = null;
if ($deck_id) {
    $student_decks = $deck_class->getDecksForStudent($student_id);
    foreach ($student_decks as $deck) {
        if ($deck['id'] == $deck_id) {
            $deck_info = $deck;
            break;
        }
    }
}

// Обработка AJAX запросов для обновления прогресса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_progress') {
        $vocabulary_id = $_POST['vocabulary_id'];
        $difficulty = $_POST['difficulty'];
        
        if ($vocabulary->updateProgress($student_id, $vocabulary_id, $difficulty)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit();
    }
    
    if ($_POST['action'] === 'get_next_card') {
        $review_mode = $_POST['review_mode'] ?? 'normal';
        
        if ($review_mode === 'today') {
            $words = $vocabulary->getWordsStudiedToday($student_id, $deck_id);
        } elseif ($review_mode === 'all_studied') {
            $words = $vocabulary->getAllStudiedWords($student_id, $deck_id);
        } else {
            $words = $vocabulary->getWordsForReview($student_id, $deck_id);
        }
        
        if (!empty($words)) {
            echo json_encode(['success' => true, 'word' => $words[0]]);
        } else {
            $message = $review_mode === 'today' ? 'Сегодня вы еще не изучили ни одного слова' : 
                      ($review_mode === 'all_studied' ? 'У вас нет изученных слов для повторения' : 'Нет больше слов для изучения');
            echo json_encode(['success' => false, 'message' => $message]);
        }
        exit();
    }
}

// Получаем слова в зависимости от режима повторения
if ($review_mode === 'today') {
    $words_for_review = $vocabulary->getWordsStudiedToday($student_id, $deck_id);
} elseif ($review_mode === 'all_studied') {
    $words_for_review = $vocabulary->getAllStudiedWords($student_id, $deck_id);
} else {
    $words_for_review = $vocabulary->getWordsForReview($student_id, $deck_id);
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="flashcards_page_title"><?php echo translate('flashcards_page_title'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 300;
        }

        .progress-info {
            display: flex;
            align-items: center;
            gap: 2rem;
            color: rgba(255,255,255,0.9);
        }

        .review-mode-controls {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .review-mode-btn {
            padding: 0.6rem 1.2rem;
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .review-mode-btn:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-2px);
        }

        .review-mode-btn.active {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
        }

        .btn {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 70vh;
        }

        .flashcard-container {
            perspective: 1000px;
            width: 100%;
            max-width: 500px;
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

        .card-face {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }

        .card-front {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .card-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: rotateY(180deg);
        }

        .card-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 300;
        }

        .card-content p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .card-back .card-content p {
            color: rgba(255,255,255,0.9);
        }

        .card-image {
            max-width: 200px;
            max-height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .controls {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            gap: 2rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .control-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 120px;
        }

        .btn-easy {
            background: #28a745;
            color: white;
        }

        .btn-easy:hover {
            background: #218838;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        .btn-hard {
            background: #dc3545;
            color: white;
        }

        .btn-hard:hover {
            background: #c82333;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(220, 53, 69, 0.3);
        }

        .no-words {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .no-words h2 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .no-words p {
            color: #666;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }

        .loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .click-hint {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .progress-info {
                gap: 1rem;
            }

            .review-mode-controls {
                margin-bottom: 0.5rem;
                gap: 0.5rem;
            }

            .review-mode-btn {
                font-size: 0.8rem;
                padding: 0.5rem 1rem;
            }

            .container {
                padding: 0 1rem;
                min-height: 60vh;
            }

            .flashcard {
                height: 350px;
            }

            .card-content h2 {
                font-size: 2rem;
            }

            .controls {
                gap: 1rem;
                margin-top: 1.5rem;
                padding: 1rem;
                flex-direction: column;
            }

            .control-btn {
                padding: 0.8rem 1.5rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="flashcards_header"><?php echo translate('flashcards_header'); ?></h1>
                <?php if ($deck_info): ?>
                    <p style="font-size: 0.9rem; opacity: 0.8; margin-top: 0.5rem;">
                        📚 <?php echo htmlspecialchars($deck_info['name']); ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="progress-info">
                <?php include 'language_switcher.php'; ?>
                <span id="progress-text"><span data-translate-key="words_remaining"><?php echo translate('words_remaining'); ?></span> <span id="words-count"><?php echo count($words_for_review); ?></span> <span data-translate-key="words_count_unit"><?php echo translate('words_count_unit'); ?></span></span>
                <a href="dashboard.php" class="btn" data-translate-key="back_button"><?php echo translate('back_button'); ?></a>
            </div>
        </div>
        
        <!-- Кнопки для переключения режима повторения -->
        <div class="review-mode-controls">
            <a href="?<?php echo http_build_query(array_merge($_GET, ['review_mode' => 'normal'])); ?>" 
               class="review-mode-btn <?php echo $review_mode === 'normal' ? 'active' : ''; ?>"
               data-translate-key="normal_learning"><?php echo translate('normal_learning'); ?>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['review_mode' => 'today'])); ?>" 
               class="review-mode-btn <?php echo $review_mode === 'today' ? 'active' : ''; ?>"
               data-translate-key="review_today_words"><?php echo translate('review_today_words'); ?>
            </a>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['review_mode' => 'all_studied'])); ?>" 
               class="review-mode-btn <?php echo $review_mode === 'all_studied' ? 'active' : ''; ?>"
               data-translate-key="review_all_studied_words"><?php echo translate('review_all_studied_words'); ?>
            </a>
        </div>
    </header>

    <div class="container">
        <?php if (empty($words_for_review)): ?>
            <div class="no-words">
                <?php if ($review_mode === 'today'): ?>
                    <h2 data-translate-key="todays_words_title"><?php echo translate('todays_words_title'); ?></h2>
                    <p data-translate-key="no_words_studied_today"><?php echo translate('no_words_studied_today'); ?></p>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['review_mode' => 'normal'])); ?>" class="btn btn-primary" data-translate-key="start_learning_button"><?php echo translate('start_learning_button'); ?></a>
                <?php elseif ($review_mode === 'all_studied'): ?>
                    <h2 data-translate-key="all_studied_words_title"><?php echo translate('all_studied_words_title'); ?></h2>
                    <p data-translate-key="no_studied_words_yet"><?php echo translate('no_studied_words_yet'); ?></p>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['review_mode' => 'normal'])); ?>" class="btn btn-primary" data-translate-key="start_learning_button"><?php echo translate('start_learning_button'); ?></a>
                <?php else: ?>
                    <h2 data-translate-key="excellent_title"><?php echo translate('excellent_title'); ?></h2>
                    <p data-translate-key="no_words_for_review_today"><?php echo translate('no_words_for_review_today'); ?></p>
                    <a href="dashboard.php" class="btn btn-primary" data-translate-key="return_to_main"><?php echo translate('return_to_main'); ?></a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="flashcard-container">
                <div class="flashcard" id="flashcard">
                    <div class="card-face card-front">
                        <div class="card-content" id="front-content">
                            <!-- Контент будет загружен через JavaScript -->
                        </div>
                        <div class="click-hint" data-translate-key="click_to_flip_hint"><?php echo translate('click_to_flip_hint'); ?></div>
                    </div>
                    <div class="card-face card-back">
                        <div class="card-content" id="back-content">
                            <!-- Контент будет загружен через JavaScript -->
                        </div>
                    </div>
                </div>
            </div>

            <div class="controls">
                <button class="control-btn btn-hard" onclick="rateWord('hard')" data-translate-key="btn_hard"><?php echo translate('btn_hard'); ?></button>
                <button class="control-btn btn-easy" onclick="rateWord('easy')" data-translate-key="btn_easy"><?php echo translate('btn_easy'); ?></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div data-translate-key="loading_next_card"><?php echo translate('loading_next_card'); ?></div>
    </div>

    <script>
        <?php
        // Функция для получения перевода по ключу для конкретного языка
        function translate_key($key, $lang) {
            global $translations;
            if (isset($translations[$lang][$key])) {
                return $translations[$lang][$key];
            }
            return $key;
        }
        ?>
        
        // Переводы для JavaScript
        const jsTranslations = {
            'kk': {
                'review_completed_title': '<?php echo addslashes(translate_key('review_completed_title', 'kk')); ?>',
                'reviewed_todays_words': '<?php echo addslashes(translate_key('reviewed_todays_words', 'kk')); ?>',
                'reviewed_all_words': '<?php echo addslashes(translate_key('reviewed_all_words', 'kk')); ?>',
                'congratulations_title': '<?php echo addslashes(translate_key('congratulations_title', 'kk')); ?>',
                'completed_todays_tasks': '<?php echo addslashes(translate_key('completed_todays_tasks', 'kk')); ?>',
                'return_to_main': '<?php echo addslashes(translate_key('return_to_main', 'kk')); ?>'
            },
            'ru': {
                'review_completed_title': '<?php echo addslashes(translate_key('review_completed_title', 'ru')); ?>',
                'reviewed_todays_words': '<?php echo addslashes(translate_key('reviewed_todays_words', 'ru')); ?>',
                'reviewed_all_words': '<?php echo addslashes(translate_key('reviewed_all_words', 'ru')); ?>',
                'congratulations_title': '<?php echo addslashes(translate_key('congratulations_title', 'ru')); ?>',
                'completed_todays_tasks': '<?php echo addslashes(translate_key('completed_todays_tasks', 'ru')); ?>',
                'return_to_main': '<?php echo addslashes(translate_key('return_to_main', 'ru')); ?>'
            },
            'en': {
                'review_completed_title': '<?php echo addslashes(translate_key('review_completed_title', 'en')); ?>',
                'reviewed_todays_words': '<?php echo addslashes(translate_key('reviewed_todays_words', 'en')); ?>',
                'reviewed_all_words': '<?php echo addslashes(translate_key('reviewed_all_words', 'en')); ?>',
                'congratulations_title': '<?php echo addslashes(translate_key('congratulations_title', 'en')); ?>',
                'completed_todays_tasks': '<?php echo addslashes(translate_key('completed_todays_tasks', 'en')); ?>',
                'return_to_main': '<?php echo addslashes(translate_key('return_to_main', 'en')); ?>'
            }
        };

        function getTranslation(key, lang) {
            return jsTranslations[lang] && jsTranslations[lang][key] ? jsTranslations[lang][key] : key;
        }

        let currentWord = null;
        let words = <?php echo json_encode($words_for_review); ?>;
        let currentIndex = 0;
        let isFlipped = false;
        let reviewMode = '<?php echo $review_mode; ?>';

        // Загружаем первую карточку
        if (words.length > 0) {
            loadCard(words[currentIndex]);
        }

        function loadCard(word) {
            currentWord = word;
            isFlipped = false;
            
            // Сбрасываем поворот карточки
            document.getElementById('flashcard').classList.remove('flipped');
            
            // Случайно выбираем, что показать сначала
            const showForeignFirst = Math.random() < 0.5;
            
            const frontContent = document.getElementById('front-content');
            const backContent = document.getElementById('back-content');
            
            if (showForeignFirst) {
                // Показываем сначала иностранное слово
                frontContent.innerHTML = `
                    <h2>${escapeHtml(word.foreign_word)}</h2>
                    ${word.image_path ? `<img src="../${escapeHtml(word.image_path)}" alt="Изображение" class="card-image">` : ''}
                `;
                backContent.innerHTML = `
                    <h2>${escapeHtml(word.translation)}</h2>
                `;
            } else {
                // Показываем сначала перевод
                frontContent.innerHTML = `
                    <h2>${escapeHtml(word.translation)}</h2>
                    ${word.image_path ? `<img src="../${escapeHtml(word.image_path)}" alt="Изображение" class="card-image">` : ''}
                `;
                backContent.innerHTML = `
                    <h2>${escapeHtml(word.foreign_word)}</h2>
                `;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Обработчик клика по карточке
        document.getElementById('flashcard').addEventListener('click', function() {
            this.classList.toggle('flipped');
            isFlipped = !isFlipped;
        });

        function rateWord(difficulty) {
            if (!currentWord) return;
            
            // Показываем загрузку
            document.getElementById('loading').style.display = 'block';
            
            // Отправляем оценку на сервер
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_progress&vocabulary_id=${currentWord.id}&difficulty=${difficulty}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Переходим к следующей карточке
                    currentIndex++;
                    updateProgress();
                    
                    if (currentIndex < words.length) {
                        loadCard(words[currentIndex]);
                    } else {
                        // Загружаем новые карточки с сервера
                        loadNewCards();
                    }
                } else {
                    alert('Ошибка при сохранении прогресса');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при сохранении прогресса');
            })
            .finally(() => {
                document.getElementById('loading').style.display = 'none';
            });
        }

        function loadNewCards() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_next_card&review_mode=${reviewMode}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    words = [data.word];
                    currentIndex = 0;
                    loadCard(words[0]);
                    updateProgress();
                } else {
                    // Нет больше карточек
                    showCompletionMessage();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCompletionMessage();
            });
        }

        function updateProgress() {
            const wordsCount = document.getElementById('words-count');
            const remaining = words.length - currentIndex - 1;
            wordsCount.textContent = Math.max(0, remaining);
        }

        function showCompletionMessage() {
            // Получаем текущий язык из localStorage или используем язык с сервера по умолчанию
            const currentLang = localStorage.getItem('selectedLanguage') || '<?php echo getCurrentLanguage(); ?>';
            let title, message;
            
            if (reviewMode === 'today') {
                title = getTranslation('review_completed_title', currentLang);
                message = getTranslation('reviewed_todays_words', currentLang);
            } else if (reviewMode === 'all_studied') {
                title = getTranslation('review_completed_title', currentLang);
                message = getTranslation('reviewed_all_words', currentLang);
            } else {
                title = getTranslation('congratulations_title', currentLang);
                message = getTranslation('completed_todays_tasks', currentLang);
            }
            
            const returnText = getTranslation('return_to_main', currentLang);
            
            document.querySelector('.container').innerHTML = `
                <div class="no-words">
                    <h2>${title}</h2>
                    <p>${message}</p>
                    <a href="dashboard.php" class="btn btn-primary">${returnText}</a>
                </div>
            `;
        }

        // Обработка клавиатуры
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space') {
                e.preventDefault();
                document.getElementById('flashcard').click();
            } else if (e.code === 'ArrowLeft' || e.code === 'Digit1') {
                e.preventDefault();
                rateWord('hard');
            } else if (e.code === 'ArrowRight' || e.code === 'Digit2') {
                e.preventDefault();
                rateWord('easy');
            }
        });
    </script>
</body>
</html>
