<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Vocabulary.php';
require_once '../classes/Deck.php';

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
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Мой словарь</title>
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
                <h1>📚 Мой словарь</h1>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['first_name']); ?></span>
                <a href="dashboard.php" class="btn">← Назад</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <h2>Поиск по словарю</h2>
            <input type="text" id="searchBox" class="search-box" placeholder="Поиск слов...">
            
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterWords('all')">Все слова</button>
                <button class="filter-tab" onclick="filterWords('ready')">К повторению</button>
                <button class="filter-tab" onclick="filterWords('learned')">Изученные</button>
                <button class="filter-tab" onclick="filterWords('learning')">Изучается</button>
                <button class="filter-tab" onclick="filterWords('new')">Новые</button>
            </div>
        </div>

        <!-- Фильтры и сортировка -->
        <div class="filters">
            <div class="filter-group">
                <label for="deck-filter">Колода:</label>
                <select id="deck-filter" name="deck_id">
                    <option value="0" <?php echo $selected_deck == 0 ? 'selected' : ''; ?>>Все колоды</option>
                    <?php foreach ($student_decks as $deck): ?>
                        <option value="<?php echo $deck['id']; ?>" 
                                <?php echo $selected_deck == $deck['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($deck['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="sort-filter">Сортировка:</label>
                <select id="sort-filter" name="sort">
                    <option value="date" <?php echo $sort_by == 'date' ? 'selected' : ''; ?>>По дате добавления</option>
                    <option value="easy_first" <?php echo $sort_by == 'easy_first' ? 'selected' : ''; ?>>Сначала легкие слова</option>
                    <option value="hard_first" <?php echo $sort_by == 'hard_first' ? 'selected' : ''; ?>>Сначала трудные слова</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn-filter" onclick="applyFilters()">Применить</button>
                <a href="vocabulary_view.php" class="btn-filter secondary">Сбросить</a>
            </div>
        </div>

        <div class="card">
            <h2>Словарь <span id="wordsCount">(<?php echo count($words); ?> слов)</span></h2>
            
            <?php if (empty($words)): ?>
                <div class="no-words">
                    <h3>📝 Словарь пуст</h3>
                    <p>Ваш преподаватель пока не добавил слова для изучения.</p>
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
                            $status_text = 'Новое';
                        } elseif ($is_ready) {
                            $status_class = 'status-ready';
                            $status_text = 'К повторению';
                        } elseif ($is_learned) {
                            $status_class = 'status-learned';
                            $status_text = 'Изучено';
                        } else {
                            $status_class = 'status-learning';
                            $status_text = 'Изучается';
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
                                    <div class="stat-label">Попыток</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $word['repetition_count'] ?: 0; ?></div>
                                    <div class="stat-label">Успешных</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo number_format($word['ease_factor'] ?: 2.5, 1); ?></div>
                                    <div class="stat-label">Легкость</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value"><?php echo $word['interval_days'] ?: 1; ?></div>
                                    <div class="stat-label">Интервал (дни)</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">
                                        <?php 
                                        if ($is_ready) {
                                            echo 'Сегодня';
                                        } else {
                                            echo $next_review->format('d.m');
                                        }
                                        ?>
                                    </div>
                                    <div class="stat-label">Следующий раз</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let allWords = document.querySelectorAll('.word-card');
        let currentFilter = 'all';

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
            
            // Обновляем счетчик
            document.getElementById('wordsCount').textContent = `(${visibleCount} слов)`;
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
    </script>
</body>
</html>
