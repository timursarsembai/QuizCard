<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';
require_once '../classes/Test.php';
require_once '../includes/translations.php';

// Fallback для переводов, если система не работает
if (!function_exists('translate')) {
    function translate($key) {
        $fallbacks = [
            'test_results_title' => 'Результаты теста',
            'test_results_breadcrumb_decks' => 'Колоды',
            'test_results_breadcrumb_tests' => 'Тесты',
            'test_results_breadcrumb_results' => 'Результаты',
            'back_button' => '← Назад',
            'logout_button' => 'Выйти',
            'test_info_prefix' => 'Тест:',
            'deck_info_prefix' => 'Колода:',
            'total_attempts_stat' => 'Всего попыток',
            'unique_students_stat' => 'Уникальных учеников',
            'average_score_stat' => 'Средний балл',
            'best_results_title' => '🏆 Лучшие результаты учеников',
            'all_attempts_title' => '📋 Все попытки',
            'student_column' => 'Ученик',
            'score_column' => 'Балл',
            'correct_answers_column' => 'Правильных ответов',
            'time_column' => 'Время',
            'completion_date_column' => 'Дата прохождения',
            'correct_answers_format' => 'из',
            'no_results_title' => 'Пока нет результатов',
            'no_results_description' => 'Ученики еще не проходили этот тест'
        ];
        return $fallbacks[$key] ?? $key;
    }
}

if (!function_exists('getCurrentLanguage')) {
    function getCurrentLanguage() {
        return 'ru';
    }
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);
$test = new Test($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Проверяем test_id
if (!isset($_GET['test_id'])) {
    header("Location: decks.php");
    exit();
}

$test_id = $_GET['test_id'];
$current_test = $test->getTestById($test_id);

if (!$current_test) {
    header("Location: decks.php");
    exit();
}

// Проверяем, что тест принадлежит преподавателю
$current_deck = $deck->getDeckById($current_test['deck_id'], $teacher_id);
if (!$current_deck) {
    header("Location: decks.php");
    exit();
}

// Получаем все попытки для этого теста
$query = "SELECT ta.*, u.username, u.first_name, u.last_name
          FROM test_attempts ta
          JOIN users u ON ta.student_id = u.id
          WHERE ta.test_id = :test_id
          ORDER BY ta.completed_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':test_id', $test_id);
$stmt->execute();
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Вычисляем статистику
$total_attempts = count($attempts);
$total_score = 0;
$completed_students = [];

foreach ($attempts as $attempt) {
    $total_score += $attempt['score'];
    if (!in_array($attempt['student_id'], $completed_students)) {
        $completed_students[] = $attempt['student_id'];
    }
}

$average_score = $total_attempts > 0 ? round($total_score / $total_attempts, 1) : 0;
$unique_students = count($completed_students);

// Получаем лучшие результаты
$best_attempts = [];
if (!empty($attempts)) {
    $query = "SELECT ta.*, u.username, u.first_name, u.last_name
              FROM test_attempts ta
              JOIN users u ON ta.student_id = u.id
              WHERE ta.test_id = :test_id
              AND ta.score = (
                  SELECT MAX(score) 
                  FROM test_attempts ta2 
                  WHERE ta2.test_id = ta.test_id AND ta2.student_id = ta.student_id
              )
              GROUP BY ta.student_id
              ORDER BY ta.score DESC, ta.completed_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':test_id', $test_id);
    $stmt->execute();
    $best_attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="test_results_title">QuizCard - <?php echo translate('test_results_title'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        .header {
            background: #667eea;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
        }

        .breadcrumb {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 0.25rem;
        }

        .breadcrumb a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: white;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .btn-primary {
            background: #28a745;
            color: white;
        }

        .btn-primary:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .test-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 5px solid #667eea;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .results-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .results-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .results-table tr:hover {
            background: #f8f9fa;
        }

        .score-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .score-excellent {
            background: #d4edda;
            color: #155724;
        }

        .score-good {
            background: #d1ecf1;
            color: #0c5460;
        }

        .score-average {
            background: #fff3cd;
            color: #856404;
        }

        .score-poor {
            background: #f8d7da;
            color: #721c24;
        }

        .time-spent {
            color: #666;
            font-size: 0.9rem;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-results-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 1rem;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
            }

            .results-table {
                font-size: 0.9rem;
            }

            .results-table th,
            .results-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="test_results_title">📊 <?php echo translate('test_results_title'); ?></h1>
                <div class="breadcrumb">
                    <a href="decks.php" data-translate-key="test_results_breadcrumb_decks"><?php echo translate('test_results_breadcrumb_decks'); ?></a> → 
                    <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" data-translate-key="test_results_breadcrumb_tests"><?php echo translate('test_results_breadcrumb_tests'); ?></a> → 
                    <span data-translate-key="test_results_breadcrumb_results"><?php echo translate('test_results_breadcrumb_results'); ?></span>
                </div>
            </div>
            <div class="nav-links">
                <?php include 'language_switcher.php'; ?>
                <a href="test_manager.php?deck_id=<?php echo $current_test['deck_id']; ?>" class="btn" data-translate-key="back_button"><?php echo translate('back_button'); ?></a>
                <a href="../logout.php" class="btn" data-translate-key="logout_button"><?php echo translate('logout_button'); ?></a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="test-info">
            <h2><span data-translate-key="test_info_prefix"><?php echo translate('test_info_prefix'); ?></span> <?php echo htmlspecialchars($current_test['name']); ?></h2>
            <p><span data-translate-key="deck_info_prefix"><?php echo translate('deck_info_prefix'); ?></span> <?php echo htmlspecialchars($current_deck['name']); ?></p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_attempts; ?></div>
                <div class="stat-label" data-translate-key="total_attempts_stat"><?php echo translate('total_attempts_stat'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $unique_students; ?></div>
                <div class="stat-label" data-translate-key="unique_students_stat"><?php echo translate('unique_students_stat'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $average_score; ?>%</div>
                <div class="stat-label" data-translate-key="average_score_stat"><?php echo translate('average_score_stat'); ?></div>
            </div>
        </div>

        <?php if (!empty($best_attempts)): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="section-title" data-translate-key="best_results_title"><?php echo translate('best_results_title'); ?></div>
                </div>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th data-translate-key="student_column"><?php echo translate('student_column'); ?></th>
                            <th data-translate-key="score_column"><?php echo translate('score_column'); ?></th>
                            <th data-translate-key="correct_answers_column"><?php echo translate('correct_answers_column'); ?></th>
                            <th data-translate-key="time_column"><?php echo translate('time_column'); ?></th>
                            <th data-translate-key="completion_date_column"><?php echo translate('completion_date_column'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($best_attempts as $attempt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']); ?></strong><br>
                                    <small style="color: #666;">@<?php echo htmlspecialchars($attempt['username']); ?></small>
                                </td>
                                <td>
                                    <span class="score-badge 
                                        <?php 
                                        if ($attempt['score'] >= 90) echo 'score-excellent';
                                        elseif ($attempt['score'] >= 75) echo 'score-good';
                                        elseif ($attempt['score'] >= 60) echo 'score-average';
                                        else echo 'score-poor';
                                        ?>">
                                        <?php echo $attempt['score']; ?>%
                                    </span>
                                </td>
                                <td><?php echo $attempt['correct_answers']; ?> <span data-translate-key="correct_answers_format"><?php echo translate('correct_answers_format'); ?></span> <?php echo $attempt['total_questions']; ?></td>
                                <td>
                                    <?php if ($attempt['time_spent']): ?>
                                        <span class="time-spent">
                                            <?php 
                                            $minutes = floor($attempt['time_spent'] / 60);
                                            $seconds = $attempt['time_spent'] % 60;
                                            echo sprintf('%d:%02d', $minutes, $seconds);
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="time-spent">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($attempt['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($attempts)): ?>
            <div class="results-section">
                <div class="section-header">
                    <div class="section-title" data-translate-key="all_attempts_title"><?php echo translate('all_attempts_title'); ?></div>
                </div>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th data-translate-key="student_column"><?php echo translate('student_column'); ?></th>
                            <th data-translate-key="score_column"><?php echo translate('score_column'); ?></th>
                            <th data-translate-key="correct_answers_column"><?php echo translate('correct_answers_column'); ?></th>
                            <th data-translate-key="time_column"><?php echo translate('time_column'); ?></th>
                            <th data-translate-key="completion_date_column"><?php echo translate('completion_date_column'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']); ?></strong><br>
                                    <small style="color: #666;">@<?php echo htmlspecialchars($attempt['username']); ?></small>
                                </td>
                                <td>
                                    <span class="score-badge 
                                        <?php 
                                        if ($attempt['score'] >= 90) echo 'score-excellent';
                                        elseif ($attempt['score'] >= 75) echo 'score-good';
                                        elseif ($attempt['score'] >= 60) echo 'score-average';
                                        else echo 'score-poor';
                                        ?>">
                                        <?php echo $attempt['score']; ?>%
                                    </span>
                                </td>
                                <td><?php echo $attempt['correct_answers']; ?> <span data-translate-key="correct_answers_format"><?php echo translate('correct_answers_format'); ?></span> <?php echo $attempt['total_questions']; ?></td>
                                <td>
                                    <?php if ($attempt['time_spent']): ?>
                                        <span class="time-spent">
                                            <?php 
                                            $minutes = floor($attempt['time_spent'] / 60);
                                            $seconds = $attempt['time_spent'] % 60;
                                            echo sprintf('%d:%02d', $minutes, $seconds);
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="time-spent">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($attempt['completed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="results-section">
                <div class="no-results">
                    <div class="no-results-icon">📊</div>
                    <h3 data-translate-key="no_results_title"><?php echo translate('no_results_title'); ?></h3>
                    <p data-translate-key="no_results_description"><?php echo translate('no_results_description'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Обновление переводов после смены языка
        function updateTranslations() {
            // Обновляем все элементы с data-translate-key
            document.querySelectorAll('[data-translate-key]').forEach(element => {
                const key = element.getAttribute('data-translate-key');
                if (translations[key]) {
                    if (element.tagName.toLowerCase() === 'input' || element.tagName.toLowerCase() === 'textarea') {
                        if (element.hasAttribute('placeholder')) {
                            element.placeholder = translations[key];
                        } else {
                            element.value = translations[key];
                        }
                    } else {
                        // Сохраняем иконки и другой HTML контент
                        if (key === 'test_results_title' && element.tagName.toLowerCase() === 'h1') {
                            element.innerHTML = '📊 ' + translations[key];
                        } else if (key === 'best_results_title') {
                            element.innerHTML = '🏆 ' + translations[key];
                        } else if (key === 'all_attempts_title') {
                            element.innerHTML = '📋 ' + translations[key];
                        } else {
                            element.textContent = translations[key];
                        }
                    }
                }
            });

            // Обновляем title страницы
            if (translations['test_results_title']) {
                document.title = 'QuizCard - ' + translations['test_results_title'];
            }
        }

        // Вызываем обновление переводов при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof updateTranslations === 'function') {
                updateTranslations();
            }
        });
    </script>
</body>
</html>
