<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'QuizCard - Панель преподавателя'; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/audio.css">
    <link rel="stylesheet" href="/css/security.css">
    
    <!-- CSRF Meta Tag -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
</head>
<body>
    <header class="navbar">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="/teacher/dashboard" class="navbar-brand">
                    <i class="fas fa-graduation-cap"></i>
                    QuizCard
                </a>
                
                <nav class="navbar-nav d-flex">
                    <a href="/teacher/dashboard" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span class="d-none d-md-inline">Главная</span>
                    </a>
                    <a href="/teacher/students" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="d-none d-md-inline">Студенты</span>
                    </a>
                    <a href="/teacher/decks" class="nav-link">
                        <i class="fas fa-layer-group"></i>
                        <span class="d-none d-md-inline">Колоды</span>
                    </a>
                    <a href="/teacher/vocabulary" class="nav-link">
                        <i class="fas fa-book"></i>
                        <span class="d-none d-md-inline">Словарь</span>
                    </a>
                    <a href="/teacher/tests" class="nav-link">
                        <i class="fas fa-clipboard-list"></i>
                        <span class="d-none d-md-inline">Тесты</span>
                    </a>
                    <a href="/teacher/security-dashboard" class="nav-link">
                        <i class="fas fa-shield-alt"></i>
                        <span class="d-none d-md-inline">Безопасность</span>
                    </a>
                    <a href="/teacher/account" class="nav-link">
                        <i class="fas fa-user-cog"></i>
                        <span class="d-none d-md-inline">Аккаунт</span>
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

        .btn-active {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            color: #667eea;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            color: #666;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding: 1rem 0;
            border-bottom: 3px solid #667eea;
        }

        .page-title h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 600;
        }

        .page-icon {
            font-size: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                padding: 0 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .container {
                padding: 1rem;
            }

            .card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>
                    <span>📚</span>
                    QuizCard
                    <small style="font-size: 0.7em; opacity: 0.8;">Преподаватель</small>
                </h1>
            </div>
            <nav class="nav-links">
                <a href="/teacher/dashboard" class="btn">🏠 Главная</a>
                <a href="/teacher/students" class="btn">👥 Ученики</a>
                <a href="/teacher/decks" class="btn">📚 Колоды</a>
                <a href="/teacher/tests" class="btn">📝 Тесты</a>
                <a href="/teacher/statistics" class="btn">📊 Статистика</a>
                <a href="/teacher/account" class="btn">⚙️ Настройки</a>
                <a href="/logout" class="btn">🚪 Выход</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (isset($page_title) && isset($page_icon)): ?>
            <div class="page-title">
                <span class="page-icon"><?php echo $page_icon; ?></span>
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
        <?php endif; ?>
