<?php
// Получаем параметры фильтрации и сортировки
$selected_deck = isset($_GET['deck_id']) ? (int)$_GET['deck_id'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';

// Получаем все колоды студента для фильтра
$student_decks = $deck->getDecksForStudent($student_id);

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
    <title><?php echo translate('vocabulary_view_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filters-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            min-width: 150px;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .vocabulary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .word-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .word-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .word-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--deck-color, #007bff);
        }

        .word-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .word-main {
            flex: 1;
        }

        .word-term {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .word-definition {
            color: #666;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .word-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .deck-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .deck-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }

        .deck-name {
            font-size: 0.9rem;
            color: #666;
        }

        .difficulty-indicator {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .difficulty-stars {
            display: flex;
            gap: 0.2rem;
        }

        .star {
            width: 12px;
            height: 12px;
            background: #ddd;
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
            transition: background 0.3s ease;
        }

        .star.filled {
            background: #ffc107;
        }

        .learning-progress {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
            transition: width 0.5s ease;
        }

        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #666;
        }

        .progress-level {
            font-weight: 600;
        }

        .level-new { color: #6c757d; }
        .level-learning { color: #ffc107; }
        .level-review { color: #17a2b8; }
        .level-mastered { color: #28a745; }

        .audio-control {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 123, 255, 0.1);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .audio-control:hover {
            background: rgba(0, 123, 255, 0.2);
            transform: scale(1.1);
        }

        .audio-control i {
            color: #007bff;
            font-size: 1rem;
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .vocabulary-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-select {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>QuizCard</h1>
            </div>
            <nav class="nav-menu">
                <a href="/student/dashboard" data-translate-key="dashboard"><?php echo translate('dashboard'); ?></a>
                <a href="/student/tests" data-translate-key="tests"><?php echo translate('tests'); ?></a>
                <a href="/student/flashcards" data-translate-key="flashcards"><?php echo translate('flashcards'); ?></a>
                <a href="/student/statistics" data-translate-key="statistics"><?php echo translate('statistics'); ?></a>
                <a href="/student/vocabulary-view" class="active" data-translate-key="vocabulary"><?php echo translate('vocabulary'); ?></a>
                <a href="/logout" data-translate-key="logout"><?php echo translate('logout'); ?></a>
            </nav>
            <?php include __DIR__ . '/language_switcher.php'; ?>
        </div>
    </div>

    <div class="container">
        <header class="page-header">
            <h1 data-translate-key="vocabulary_view_title"><?php echo translate('vocabulary_view_title'); ?></h1>
            <p data-translate-key="vocabulary_view_subtitle"><?php echo translate('vocabulary_view_subtitle'); ?></p>
        </header>

        <div class="filters-container">
            <form method="GET" class="filters-row">
                <div class="filter-group">
                    <label class="filter-label" data-translate-key="filter_by_deck"><?php echo translate('filter_by_deck'); ?></label>
                    <select name="deck_id" class="filter-select" onchange="this.form.submit()">
                        <option value="0" data-translate-key="all_decks"><?php echo translate('all_decks'); ?></option>
                        <?php foreach ($student_decks as $deck_item): ?>
                            <option value="<?php echo $deck_item['id']; ?>" 
                                    <?php echo $selected_deck == $deck_item['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deck_item['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label" data-translate-key="sort_by"><?php echo translate('sort_by'); ?></label>
                    <select name="sort" class="filter-select" onchange="this.form.submit()">
                        <option value="date" <?php echo $sort_by == 'date' ? 'selected' : ''; ?> data-translate-key="sort_by_date">
                            <?php echo translate('sort_by_date'); ?>
                        </option>
                        <option value="easy_first" <?php echo $sort_by == 'easy_first' ? 'selected' : ''; ?> data-translate-key="sort_easy_first">
                            <?php echo translate('sort_easy_first'); ?>
                        </option>
                        <option value="hard_first" <?php echo $sort_by == 'hard_first' ? 'selected' : ''; ?> data-translate-key="sort_hard_first">
                            <?php echo translate('sort_hard_first'); ?>
                        </option>
                    </select>
                </div>
                <input type="hidden" name="deck_id" value="<?php echo $selected_deck; ?>">
            </form>
        </div>

        <?php
        // Подсчитываем статистику
        $total_words = count($words);
        $new_words = count(array_filter($words, function($w) { return !$w['total_attempts'] || $w['total_attempts'] == 0; }));
        $learning_words = count(array_filter($words, function($w) { return $w['total_attempts'] > 0 && ($w['repetition_count'] ?: 0) < 3; }));
        $mastered_words = count(array_filter($words, function($w) { return ($w['repetition_count'] ?: 0) >= 3; }));
        ?>

        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_words; ?></div>
                <div class="stat-label" data-translate-key="total_words"><?php echo translate('total_words'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $new_words; ?></div>
                <div class="stat-label" data-translate-key="new_words"><?php echo translate('new_words'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $learning_words; ?></div>
                <div class="stat-label" data-translate-key="learning_words"><?php echo translate('learning_words'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $mastered_words; ?></div>
                <div class="stat-label" data-translate-key="mastered_words"><?php echo translate('mastered_words'); ?></div>
            </div>
        </div>

        <div class="vocabulary-grid">
            <?php if (empty($words)): ?>
                <div class="empty-state">
                    <h3 data-translate-key="no_vocabulary_title"><?php echo translate('no_vocabulary_title'); ?></h3>
                    <p data-translate-key="no_vocabulary_desc"><?php echo translate('no_vocabulary_desc'); ?></p>
                    <a href="/student/flashcards" class="btn btn-primary" data-translate-key="start_learning">
                        <?php echo translate('start_learning'); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($words as $word): ?>
                    <div class="word-card" style="--deck-color: <?php echo htmlspecialchars($word['deck_color']); ?>">
                        <?php if ($word['audio_file']): ?>
                            <button class="audio-control" onclick="playAudio('<?php echo htmlspecialchars($word['audio_file']); ?>')">
                                <i class="fas fa-volume-up"></i>
                            </button>
                        <?php endif; ?>

                        <div class="word-header">
                            <div class="word-main">
                                <div class="word-term"><?php echo htmlspecialchars($word['term']); ?></div>
                                <div class="word-definition"><?php echo htmlspecialchars($word['definition']); ?></div>
                            </div>
                        </div>

                        <div class="word-meta">
                            <div class="deck-info">
                                <div class="deck-color" style="background-color: <?php echo htmlspecialchars($word['deck_color']); ?>"></div>
                                <div class="deck-name"><?php echo htmlspecialchars($word['deck_name']); ?></div>
                            </div>

                            <div class="difficulty-indicator">
                                <div class="difficulty-stars">
                                    <?php 
                                    $ease_factor = $word['ease_factor'] ?: 2.5;
                                    $difficulty = 5 - min(4, max(0, floor(($ease_factor - 1.3) / 0.4)));
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <div class="star <?php echo $i <= $difficulty ? 'filled' : ''; ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>

                        <div class="learning-progress">
                            <?php 
                            $repetitions = $word['repetition_count'] ?: 0;
                            $progress = min(100, ($repetitions / 3) * 100);
                            
                            if ($repetitions == 0) {
                                $level = 'new';
                                $level_text = translate('level_new');
                            } elseif ($repetitions < 3) {
                                $level = 'learning';
                                $level_text = translate('level_learning');
                            } else {
                                $level = 'mastered';
                                $level_text = translate('level_mastered');
                            }
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                            <div class="progress-text">
                                <span class="progress-level level-<?php echo $level; ?>"><?php echo $level_text; ?></span>
                                <span><?php echo $repetitions; ?>/3 <span data-translate-key="repetitions"><?php echo translate('repetitions'); ?></span></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="/public/js/security.js"></script>
    <script>
        function playAudio(filename) {
            const audio = new Audio('/public/audio/' + filename);
            audio.play().catch(e => {
                console.error('Error playing audio:', e);
            });
        }
    </script>
</body>
</html>
