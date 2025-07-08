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
$deck = new Deck($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'student') {
    header("Location: ../student_login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$statistics = $vocabulary->getStatistics($student_id);
$words_for_review = $vocabulary->getWordsForReview($student_id);
$student_decks = $deck->getDecksForStudent($student_id);
$daily_limits = $vocabulary->getDailyLimitStatistics($student_id);

// Получаем количество слов в процессе изучения
$query = "SELECT COUNT(*) as studying_count FROM learning_progress WHERE student_id = :student_id AND total_attempts > 0 AND repetition_count < 3";
$stmt = $db->prepare($query);
$stmt->bindParam(':student_id', $student_id);
$stmt->execute();
$studying_result = $stmt->fetch(PDO::FETCH_ASSOC);
$studying_words = $studying_result['studying_count'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Панель ученика</title>
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
            font-size: 1.8rem;
            font-weight: 300;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }

        .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: #667eea;
            color: white;
            text-align: center;
        }

        .btn-primary:hover {
            background: #5171ff;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .stat-description {
            color: #999;
            font-size: 0.8rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }

        .card h2 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .action-card h3 {
            color: #667eea;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .action-card p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 5px;
            transition: width 0.3s;
        }

        .decks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .deck-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 5px solid;
        }

        .deck-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .deck-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .deck-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .deck-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(248, 249, 250, 0.7);
            border-radius: 8px;
        }

        .deck-stats .stat-item {
            text-align: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 6px;
        }

        .deck-stats .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.25rem;
            text-align: center;
        }

        .deck-stats .stat-label {
            font-size: 0.85rem;
            color: #666;
            text-align: center;
        }

        .deck-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            border: 1px solid rgba(102, 126, 234, 0.3);
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.5);
            transform: translateY(-1px);
        }

        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .container {
                padding: 0 1rem;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .action-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .decks-grid {
                grid-template-columns: 1fr;
            }

            .deck-stats {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
                gap: 0.5rem;
            }

            .deck-actions {
                margin-top: 0.5rem;
            }

            .btn-small {
                font-size: 0.8rem;
                padding: 0.4rem 0.6rem;
            }
        }

        @media (max-width: 480px) {
            .stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>🎓 QuizCard</h1>
            </div>
            <div class="user-info">
                <span>Привет, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</span>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['total_words'] ?: 0; ?></div>
                <div class="stat-label">Всего слов</div>
                <div class="stat-description">в вашем словаре</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['words_to_review'] ?: 0; ?></div>
                <div class="stat-label">К изучению</div>
                <div class="stat-description">готовы к повторению</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $studying_words ?: 0; ?></div>
                <div class="stat-label">На изучении</div>
                <div class="stat-description">в процессе изучения</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['total_repetitions'] ?: 0; ?></div>
                <div class="stat-label">Повторений</div>
                <div class="stat-description">выполнено всего</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $statistics['total_decks'] ?: 0; ?></div>
                <div class="stat-label">Колод</div>
                <div class="stat-description">назначено преподавателем</div>
            </div>
        </div>

        <div class="action-cards">
            <div class="action-card">
                <div class="action-icon">🎯</div>
                <h3>Карточки для изучения</h3>
                <p>Изучайте новые слова и повторяйте уже изученные с помощью интерактивных карточек. Система сама определит оптимальное время для повторения каждого слова.</p>
                
                <?php if (count($words_for_review) > 0): ?>
                    <a href="flashcards.php" class="btn btn-primary">Начать изучение (<?php echo count($words_for_review); ?> слов)</a>
                <?php else: ?>
                    <div style="color: #28a745; font-weight: 500;">Сегодня нет слов для повторения!</div>
                <?php endif; ?>
            </div>

            <div class="action-card">
                <div class="action-icon">🧪</div>
                <h3>Тесты по колодам</h3>
                <p>Проверьте свои знания с помощью тестов. Получите оценку и увидьте детальную статистику по ошибкам.</p>
                <a href="tests.php" class="btn btn-primary">Пройти тесты</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📚</div>
                <h3>Мой словарь</h3>
                <p>Просмотрите все слова в вашем словаре, отследите прогресс изучения и посмотрите статистику по каждому слову.</p>
                <a href="vocabulary_view.php" class="btn btn-primary">Открыть словарь</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📊</div>
                <h3>Статистика обучения</h3>
                <p>Отслеживайте свой прогресс, смотрите графики обучения и анализируйте эффективность запоминания слов.</p>
                <a href="statistics.php" class="btn btn-primary">Посмотреть статистику</a>
            </div>
        </div>

        <?php if (!empty($student_decks)): ?>
            <div class="card">
                <h2>📚 Мои колоды</h2>
                <div class="decks-grid">                <?php foreach ($student_decks as $deck_item): 
                    // Найдем информацию о дневном лимите для этой колоды
                    $daily_limit_info = null;
                    foreach ($daily_limits as $limit_info) {
                        if ($limit_info['id'] == $deck_item['id']) {
                            $daily_limit_info = $limit_info;
                            break;
                        }
                    }
                ?>
                    <div class="deck-card" style="border-left-color: <?php echo htmlspecialchars($deck_item['color']); ?>">
                        <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                        <?php if ($deck_item['description']): ?>
                            <div class="deck-description"><?php echo htmlspecialchars($deck_item['description']); ?></div>
                        <?php endif; ?>
                        
                        <div class="deck-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $deck_item['total_words'] ?: 0; ?></div>
                                <div class="stat-label">Слов</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $deck_item['words_to_review'] ?: 0; ?></div>
                                <div class="stat-label">К изучению</div>
                            </div>
                            <?php if ($daily_limit_info): ?>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $daily_limit_info['words_studied_today']; ?>/<?php echo $daily_limit_info['daily_limit']; ?></div>
                                    <div class="stat-label">Сегодня</div>
                                </div>
                            <?php endif; ?>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo date('d.m', strtotime($deck_item['assigned_at'])); ?></div>
                                <div class="stat-label">Назначено</div>
                            </div>
                        </div>
                        
                        <div class="deck-actions">
                            <?php if ($deck_item['words_to_review'] > 0): ?>
                                <?php 
                                $can_study = !$daily_limit_info || $daily_limit_info['can_study_more'] || $daily_limit_info['remaining_today'] > 0;
                                ?>
                                <a href="flashcards.php?deck_id=<?php echo $deck_item['id']; ?>" class="btn btn-primary">
                                    📅 Изучать (<?php echo $deck_item['words_to_review']; ?> слов)
                                </a>
                                <?php if ($daily_limit_info && $daily_limit_info['remaining_today'] <= 0): ?>
                                    <div style="color: #ffa500; font-size: 0.9rem; text-align: center; margin-top: 0.5rem;">
                                        ⏳ Дневной лимит достигнут (повторения доступны)
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="color: #28a745; font-weight: 500; text-align: center; padding: 0.5rem;">
                                    ✅ Колода изучена
                                </div>
                            <?php endif; ?>
                            
                            <!-- Кнопки принудительного повторения -->
                            <a href="flashcards.php?deck_id=<?php echo $deck_item['id']; ?>&review_mode=today" 
                               class="btn btn-secondary btn-small">
                                🔄 Повторить сегодняшние
                            </a>
                            <a href="flashcards.php?deck_id=<?php echo $deck_item['id']; ?>&review_mode=all_studied" 
                               class="btn btn-secondary btn-small">
                                📖 Повторить все изученные
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($statistics['total_words'] > 0): ?>
            <div class="stat-card" style="margin-top: 2rem;">
                <h3 style="color: #667eea; margin-bottom: 1rem;">Прогресс изучения</h3>
                <?php 
                // Получаем количество изученных слов (с repetition_count >= 3)
                $query = "SELECT COUNT(*) as learned_count FROM learning_progress WHERE student_id = :student_id AND repetition_count >= 3";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->execute();
                $learned_result = $stmt->fetch(PDO::FETCH_ASSOC);
                $learned_words = $learned_result['learned_count'];
                
                $progress_percent = ($learned_words / $statistics['total_words']) * 100;
                ?>
                <p>Изучено: <?php echo $learned_words; ?> из <?php echo $statistics['total_words']; ?> слов (<?php echo number_format($progress_percent, 1); ?>%)</p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
