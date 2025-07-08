<?php
// Этот файл предполагается для включения в другие PHP скрипты,
// поэтому здесь нет необходимости в session_start() или require_once,
// так как они уже должны быть в вызывающем файле.
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'QuizCard - Панель преподавателя'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Общие стили */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
            color: #333;
        }

        .header {
            background: #667eea;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
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
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-links {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            background: transparent;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            font-weight: 500;
        }

        .btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .btn.active {
             background: rgba(255, 255, 255, 0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
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

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
    </style>
    <!-- Page-specific styles can be added in the pages themselves -->
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1><i class="<?php echo isset($page_icon) ? $page_icon : 'fas fa-chalkboard-teacher'; ?>"></i> <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'QuizCard'; ?></h1>
            </div>
            <div class="nav-links">
                <a href="dashboard.php" class="btn <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Главная</a>
                <a href="decks.php" class="btn <?php echo basename($_SERVER['PHP_SELF']) == 'decks.php' ? 'active' : ''; ?>">Колоды</a>
                <a href="tests.php" class="btn <?php echo basename($_SERVER['PHP_SELF']) == 'tests.php' ? 'active' : ''; ?>">Тесты</a>
                <a href="students.php" class="btn <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">Ученики</a>
                <a href="account.php" class="btn <?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">Аккаунт</a>
                <a href="../logout.php" class="btn">Выйти</a>
            </div>
        </div>
    </header>
