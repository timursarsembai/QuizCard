<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

// Проверяем, что deck_id принадлежит данному преподавателю
if (!isset($_GET['deck_id'])) {
    header("Location: decks.php");
    exit();
}

$deck_id = $_GET['deck_id'];
$current_deck = $deck->getDeckById($deck_id, $teacher_id);

if (!$current_deck) {
    header("Location: decks.php");
    exit();
}

// Обработка назначения колоды ученику
if ($_POST && isset($_POST['assign_student'])) {
    $student_id = $_POST['student_id'];
    if ($deck->assignDeckToStudent($deck_id, $student_id, $teacher_id)) {
        $success = "Ученик успешно добавлен в колоду!";
    } else {
        $error = "Ошибка при добавлении ученика в колоду";
    }
}

// Обработка отмены назначения колоды ученику
if ($_GET && isset($_GET['unassign'])) {
    $student_id = $_GET['unassign'];
    if ($deck->unassignDeckFromStudent($deck_id, $student_id, $teacher_id)) {
        $success = "Ученик исключен из колоды!";
    } else {
        $error = "Ошибка при исключении ученика";
    }
}

$assigned_students = $deck->getStudentsForDeck($deck_id, $teacher_id);
$available_students = $deck->getAvailableStudents($deck_id, $teacher_id);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizCard - Добавить учеников</title>
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
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
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
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .deck-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
            border-left: 5px solid;
        }

        .deck-info h2 {
            border: none;
            color: white;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .student-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .student-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .student-username {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .student-date {
            font-size: 0.8rem;
            color: #999;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
        }

        .empty-state h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
            }

            .student-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }

            .container {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>👥 Добавить учеников</h1>
                <div class="breadcrumb">
                    <a href="decks.php">Колоды</a> → Добавление учеников
                </div>
            </div>
            <div class="nav-links">
                <a href="decks.php" class="btn">← Назад</a>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="deck-info" style="border-left-color: <?php echo htmlspecialchars($current_deck['color']); ?>">
            <h2><?php echo htmlspecialchars($current_deck['name']); ?></h2>
            <?php if ($current_deck['description']): ?>
                <p><?php echo htmlspecialchars($current_deck['description']); ?></p>
            <?php endif; ?>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($available_students)): ?>
            <div class="card">
                <h2>Добавить ученика в колоду</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_id">Выберите ученика:</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">-- Выберите ученика --</option>
                            <?php foreach ($available_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                                    (@<?php echo htmlspecialchars($student['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_student" class="btn btn-primary">Добавить в колоду</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Ученики в колоде</h2>
            <?php if (empty($assigned_students)): ?>
                <div class="empty-state">
                    <h3>👥 Нет учеников в колоде</h3>
                    <p>В эту колоду пока не добавлен ни один ученик.</p>
                </div>
            <?php else: ?>
                <div class="student-grid">
                    <?php foreach ($assigned_students as $student): ?>
                        <div class="student-card">
                            <div class="student-name">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            </div>
                            <div class="student-username">
                                @<?php echo htmlspecialchars($student['username']); ?>
                            </div>
                            <div class="student-date">
                                Назначено: <?php echo date('d.m.Y', strtotime($student['assigned_at'])); ?>
                            </div>
                            <div class="actions" style="margin-top: 1rem;">
                                <a href="?deck_id=<?php echo $deck_id; ?>&unassign=<?php echo $student['id']; ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Вы уверены, что хотите отменить назначение этой колоды?')">
                                   Отменить назначение
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($available_students) && !empty($assigned_students)): ?>
            <div class="card">
                <div class="empty-state">
                    <h3>✅ Все ученики назначены</h3>
                    <p>Колода назначена всем доступным ученикам.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Автоматическое скрытие уведомлений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
