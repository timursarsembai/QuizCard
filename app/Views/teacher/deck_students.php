<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="deck_students_title">Добавить учеников</title>
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
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

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        select, input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
        }

        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .student-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .student-info h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }

        .student-info p {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>👥 Управление учениками</h1>
                <div class="breadcrumb">
                    <a href="/teacher/decks">Колоды</a> → Ученики: <?php echo htmlspecialchars($current_deck['name']); ?>
                </div>
            </div>
            <div class="nav-links">
                <a href="/teacher/decks" class="btn">← Назад</a>
                <a href="/logout" class="btn">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Добавление нового ученика -->
        <?php if (!empty($available_students)): ?>
            <div class="card">
                <h2>➕ Добавить ученика к колоде</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="student_id">Выберите ученика:</label>
                        <select name="student_id" id="student_id" required>
                            <option value="">-- Выберите ученика --</option>
                            <?php foreach ($available_students as $student): ?>
                                <option value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    (<?php echo htmlspecialchars($student['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="assign_student" class="btn btn-primary">Добавить ученика</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Назначенные ученики -->
        <div class="card">
            <h2>👥 Назначенные ученики (<?php echo count($assigned_students); ?>)</h2>
            
            <?php if (empty($assigned_students)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">
                    К этой колоде пока не назначены ученики
                </p>
            <?php else: ?>
                <div class="students-grid">
                    <?php foreach ($assigned_students as $student): ?>
                        <div class="student-card">
                            <div class="student-info">
                                <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                <p><?php echo htmlspecialchars($student['email']); ?></p>
                                <p>Дата назначения: <?php echo date('d.m.Y', strtotime($student['assigned_at'])); ?></p>
                            </div>
                            <a href="?deck_id=<?php echo $deck_id; ?>&unassign=<?php echo $student['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Вы уверены, что хотите удалить этого ученика из колоды?')">
                                🗑️
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($available_students) && !empty($assigned_students)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">
                    Все ваши ученики уже назначены к этой колоде
                </p>
            </div>
        <?php elseif (empty($available_students) && empty($assigned_students)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">
                    У вас пока нет учеников. <a href="/teacher/students">Добавьте учеников</a> чтобы назначить их к колоде.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
