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

// Получаем параметры фильтрации и сортировки
$selected_deck = isset($_GET['deck_id']) ? (int)$_GET['deck_id'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';

// Получаем все колоды студента для фильтра
$student_decks = $deck_class->getDecksForStudent($student_id);

// Получаем слова
$words = $vocabulary->getVocabularyByStudent($student_id);

// Фильтрация по колоде
if ($selected_deck > 0) {
    $words = array_filter($words, function($word) use ($selected_deck) {
        return $word['deck_id'] == $selected_deck;
    });
}

// Сортировка
switch ($sort_by) {
    case 'easy_first':
        usort($words, function($a, $b) {
            $ease_a = $a['ease_factor'] ?: 2.5;
            $ease_b = $b['ease_factor'] ?: 2.5;
            return $ease_b <=> $ease_a; // От легких к трудным (высокий ease_factor = легкое)
        });
        break;
    case 'hard_first':
        usort($words, function($a, $b) {
            $ease_a = $a['ease_factor'] ?: 2.5;
            $ease_b = $b['ease_factor'] ?: 2.5;
            return $ease_a <=> $ease_b; // От трудных к легким (низкий ease_factor = трудное)
        });
        break;
    case 'date':
    default:
        // Оставляем сортировку по умолчанию (по дате создания)
        break;
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - <?php echo translate('my_vocabulary_title'); ?></title>
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
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 300;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            font-weight: 300;
        }

        .filters {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-group select {
            padding: 0.5rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            color: #333;
            font-size: 0.9rem;
            min-width: 150px;
            transition: border-color 0.3s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-left: auto;
        }

        .btn-filter {
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-filter:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-filter.secondary {
            background: #6c757d;
        }

        .btn-filter.secondary:hover {
            background: #545b62;
        }

        .search-box {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 25px;
            font-size: 1rem;
            margin-bottom: 2rem;
            transition: border-color 0.3s;
        }

        .search-box:focus {
            outline: none;
            border-color: #667eea;
        }

        .vocabulary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .word-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid #667eea;
        }

        .word-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .word-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .word-foreign {
            font-size: 1.3rem;
            font-weight: 600;
            color: #667eea;
        }

        .word-translation {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .word-image {
            width: 100%;
            max-height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .word-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #666;
            margin-top: 0.25rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-ready {
            background: #fff3cd;
            color: #856404;
        }

        .status-learned {
            background: #d4edda;
            color: #155724;
        }

        .status-new {
            background: #e2e3e5;
            color: #495057;
        }

        .status-learning {
            background: #cce7ff;
            color: #004085;
        }

        .no-words {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .no-words h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filter-tab {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.3);
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-tab.active {
            background: white;
            color: #667eea;
        }

        .filter-tab:hover {
            background: rgba(255,255,255,0.5);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 0 1rem;
            }

            .vocabulary-grid {
                grid-template-columns: 1fr;
            }

            .word-stats {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .filters {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .filter-actions {
                margin-left: 0;
                justify-content: center;
            }

            .filter-tabs {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="my_vocabulary_title">📚 <?php echo translate('my_vocabulary_title'); ?></h1>
            </div>
            <div class="user-info">
                <?php include 'language_switcher.php'; ?>
                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                <a href="dashboard.php" class="btn" data-translate-key="back_to_dashboard"><?php echo translate('back_to_dashboard'); ?></a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2 data-translate-key="vocabulary_search_title"><?php echo translate('vocabulary_search_title'); ?></h2>
            <input type="text" id="searchBox" class="search-box" placeholder="<?php echo translate('search_words_placeholder'); ?>" data-translate-key="search_words_placeholder">
            
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterWords('all')" data-translate-key="filter_all_words"><?php echo translate('filter_all_words'); ?></button>
                <button class="filter-tab" onclick="filterWords('ready')" data-translate-key="filter_ready_words"><?php echo translate('filter_ready_words'); ?></button>
                <button class="filter-tab" onclick="filterWords('learned')" data-translate-key="filter_learned_words"><?php echo translate('filter_learned_words'); ?></button>
                <button class="filter-tab" onclick="filterWords('learning')" data-translate-key="filter_learning_words"><?php echo translate('filter_learning_words'); ?></button>
                <button class="filter-tab" onclick="filterWords('new')" data-translate-key="filter_new_words"><?php echo translate('filter_new_words'); ?></button>
            </div>
        </div>

        <!-- Фильтры и сортировка -->
        <div class="filters">
            <div class="filter-group">
                <label for="deck-filter" data-translate-key="filter_deck_label"><?php echo translate('filter_deck_label'); ?></label>
                <select id="deck-filter" name="deck_id">
                    <option value="0" <?php echo $selected_deck == 0 ? 'selected' : ''; ?> data-translate-key="filter_all_decks"><?php echo translate('filter_all_decks'); ?></option>
                    <?php foreach ($student_decks as $deck): ?>
                        <option value="<?php echo $deck['id']; ?>" 
                                <?php echo $selected_deck == $deck['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($deck['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-filter" data-translate-key="filter_sort_label"><?php echo translate('filter_sort_label'); ?></label>
                <select id="sort-filter" name="sort">
                    <option value="date" <?php echo $sort_by == 'date' ? 'selected' : ''; ?> data-translate-key="filter_sort_date"><?php echo translate('filter_sort_date'); ?></option>
                    <option value="easy_first" <?php echo $sort_by == 'easy_first' ? 'selected' : ''; ?> data-translate-key="filter_sort_easy_first"><?php echo translate('filter_sort_easy_first'); ?></option>
                    <option value="hard_first" <?php echo $sort_by == 'hard_first' ? 'selected' : ''; ?> data-translate-key="filter_sort_hard_first"><?php echo translate('filter_sort_hard_first'); ?></option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn-filter" onclick="applyFilters()" data-translate-key="filter_apply_button"><?php echo translate('filter_apply_button'); ?></button>
                <a href="vocabulary_view.php" class="btn-filter secondary" data-translate-key="filter_reset_button"><?php echo translate('filter_reset_button'); ?></a>
            </div>
        </div>

        <div class="card">
            <h2 data-translate-key="vocabulary_words_count"><?php echo str_replace('{count}', count($words), translate('vocabulary_words_count')); ?></h2>
            
            <?php if (empty($words)): ?>
                <div class="no-words">
                    <h3 data-translate-key="vocabulary_empty_title">📝 <?php echo translate('vocabulary_empty_title'); ?></h3>
                    <p data-translate-key="vocabulary_empty_desc"><?php echo translate('vocabulary_empty_desc'); ?></p>
                </div>
            <?php else: ?>
                <div class="vocabulary-grid" id="vocabularyGrid">
                    <?php foreach ($words as $word): ?>
                        <?php
                        $next_review = new DateTime($word['next_review_date']);
                        $today = new DateTime();
                        $total_attempts = $word['total_attempts'] ?? 0;
                        $is_new = ($total_attempts == 0);
                        $is_ready = !$is_new && $word['next_review_date'] <= date('Y-m-d');
                        $is_learned = !$is_new && !$is_ready && $word['repetition_count'] > 2;
                        
                        if ($is_new) {
                            $status_class = 'status-new';
                            $status_text = translate('word_status_new');
                        } elseif ($is_ready) {
                            $status_class = 'status-ready';
                            $status_text = translate('word_status_ready');
                        } elseif ($is_learned) {
                            $status_class = 'status-learned';
                            $status_text = translate('word_status_learned');
                        } else {
                            $status_class = 'status-learning';
                            $status_text = translate('word_status_learning');
                        }
                        ?>
                        <div class="word-card" data-foreign="<?php echo strtolower(htmlspecialchars($word['foreign_word'])); ?>" 
                             data-translation="<?php echo strtolower(htmlspecialchars($word['translation'])); ?>"
                             data-status="<?php echo $is_ready ? 'ready' : ($is_learned ? 'learned' : ($is_new ? 'new' : 'learning')); ?>">
                            
                            <div class="word-header">
                                <div class="word-foreign"><?php echo htmlspecialchars($word['foreign_word']); ?></div>
                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                            </div>
                            
                            <div class="word-translation"><?php echo htmlspecialchars($word['translation']); ?></div>
                            
                            <?php if ($word['image_path']): ?>
                                <img src="../<?php echo htmlspecialchars($word['image_path']); ?>" 
                                     alt="Изображение" class="word-image">
                            <?php endif; ?>
                            
                            <div class="word-stats">
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $word['total_attempts'] ?: 0; ?></div>
                                    <div class="stat-label" data-translate-key="word_stat_attempts"><?php echo translate('word_stat_attempts'); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $word['repetition_count'] ?: 0; ?></div>
                                    <div class="stat-label" data-translate-key="word_stat_successful"><?php echo translate('word_stat_successful'); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo number_format($word['ease_factor'] ?: 2.5, 1); ?></div>
                                    <div class="stat-label" data-translate-key="word_stat_ease"><?php echo translate('word_stat_ease'); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $word['interval_days'] ?: 1; ?></div>
                                    <div class="stat-label" data-translate-key="word_stat_interval"><?php echo translate('word_stat_interval'); ?></div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php 
                                        if ($is_ready) {
                                            echo translate('word_review_today');
                                        } else {
                                            echo $next_review->format('d.m');
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label" data-translate-key="word_stat_next_review"><?php echo translate('word_stat_next_review'); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Переводы для JavaScript (глобальная переменная)
        window.jsTranslations = {
            vocabulary_words_count: "<?php echo translate('vocabulary_words_count'); ?>",
            current_language: "<?php echo getCurrentLanguage(); ?>"
        };
        
        // Общее количество слов для правильного отображения при переключении языка
        const totalWordsCount = <?php echo count($words); ?>;
        
        let allWords = document.querySelectorAll('.word-card');
        let currentFilter = 'all';

        // Глобальная функция для обновления переводов с плейсхолдерами 
        window.updateVocabularyTranslations = function() {
            // Обновляем заголовок с количеством слов
            const mainTitle = document.querySelector('[data-translate-key="vocabulary_words_count"]');
            if (mainTitle && typeof translations !== 'undefined') {
                const currentLang = document.documentElement.lang || 'ru';
                const langTranslations = translations[currentLang] || translations['ru'];
                if (langTranslations && langTranslations['vocabulary_words_count']) {
                    // Получаем текущее количество видимых слов
                    const visibleCards = document.querySelectorAll('.word-card:not([style*="display: none"])');
                    const visibleCount = visibleCards.length;
                    const translatedText = langTranslations['vocabulary_words_count'].replace('{count}', visibleCount);
                    mainTitle.textContent = translatedText;
                }
            }
        };

        // Поиск
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterAndSearch(currentFilter, searchTerm);
        });

        function filterWords(status) {
            currentFilter = status;
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            
            // Обновляем активную вкладку
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            filterAndSearch(status, searchTerm);
        }

        function filterAndSearch(status, searchTerm) {
            let visibleCount = 0;
            
            allWords.forEach(card => {
                const foreign = card.getAttribute('data-foreign');
                const translation = card.getAttribute('data-translation');
                const cardStatus = card.getAttribute('data-status');
                
                const matchesSearch = !searchTerm || 
                    foreign.includes(searchTerm) || 
                    translation.includes(searchTerm);
                
                const matchesFilter = status === 'all' || cardStatus === status;
                
                if (matchesSearch && matchesFilter) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Обновляем заголовок с правильным переводом и количеством
            const mainTitle = document.querySelector('[data-translate-key="vocabulary_words_count"]');
            if (mainTitle) {
                // Используем jsTranslations для текущего языка
                const translatedText = window.jsTranslations.vocabulary_words_count.replace('{count}', visibleCount);
                mainTitle.textContent = translatedText;
            }
        }
        
        // Функция для получения правильной формы слова "слов" в зависимости от языка и числа
        function getWordsLabel(count) {
            const lang = window.jsTranslations.current_language;
            
            if (lang === 'ru') {
                if (count % 10 === 1 && count % 100 !== 11) {
                    return 'слово';
                } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
                    return 'слова';
                } else {
                    return 'слов';
                }
            } else if (lang === 'kk') {
                return 'сөз';
            } else { // en
                return count === 1 ? 'word' : 'words';
            }
        }

        // Функция применения фильтров
        function applyFilters() {
            const deckId = document.getElementById('deck-filter').value;
            const sortBy = document.getElementById('sort-filter').value;
            
            const params = new URLSearchParams();
            if (deckId !== '0') {
                params.append('deck_id', deckId);
            }
            if (sortBy !== 'date') {
                params.append('sort', sortBy);
            }
            
            const queryString = params.toString();
            const newUrl = 'vocabulary_view.php' + (queryString ? '?' + queryString : '');
            window.location.href = newUrl;
        }

        // Анимация появления карточек
        window.addEventListener('load', function() {
            allWords.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.3s, transform 0.3s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Переопределяем updateTranslations из language_switcher.php для обработки плейсхолдеров
        document.addEventListener('DOMContentLoaded', function() {
            // Сохраняем оригинальную функцию updateTranslations
            const originalUpdateTranslations = window.updateTranslations;
            
            // Переопределяем функцию updateTranslations
            window.updateTranslations = function() {
                // Вызываем оригинальную функцию
                if (originalUpdateTranslations) {
                    originalUpdateTranslations();
                }
                
                // Обновляем jsTranslations для нового языка
                const currentLang = document.documentElement.lang || 'ru';
                if (typeof translations !== 'undefined' && translations[currentLang]) {
                    window.jsTranslations.vocabulary_words_count = translations[currentLang]['vocabulary_words_count'] || window.jsTranslations.vocabulary_words_count;
                    window.jsTranslations.current_language = currentLang;
                }
                
                // Дополнительно обрабатываем элементы с плейсхолдерами
                if (typeof window.updateVocabularyTranslations === 'function') {
                    window.updateVocabularyTranslations();
                } else {
                    // Если функция не доступна, обновляем вручную
                    const mainTitle = document.querySelector('[data-translate-key="vocabulary_words_count"]');
                    if (mainTitle) {
                        const visibleCards = document.querySelectorAll('.word-card:not([style*="display: none"])');
                        const visibleCount = visibleCards.length;
                        const translatedText = window.jsTranslations.vocabulary_words_count.replace('{count}', visibleCount);
                        mainTitle.textContent = translatedText;
                    }
                }
            };
        });
    </script>
</body>
</html>
