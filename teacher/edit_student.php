<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$error = '';
$success = '';
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные ученика
$student_info = $user->getStudentInfo($student_id, $teacher_id);
if (!$student_info) {
    header("Location: dashboard.php");
    exit();
}

// Обработка формы обновления
if ($_POST && isset($_POST['update_student'])) {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $new_first_name = trim($_POST['first_name']);
    $new_last_name = trim($_POST['last_name']);
    
    // Валидация
    if (empty($new_username) || empty($new_first_name) || empty($new_last_name)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        // Обновляем данные
        $password_to_update = !empty($new_password) ? $new_password : null;
        
        if ($user->updateStudent($student_id, $teacher_id, $new_username, $password_to_update, $new_first_name, $new_last_name)) {
            $success = 'Данные ученика успешно обновлены';
            // Обновляем локальные данные для отображения
            $student_info['username'] = $new_username;
            $student_info['first_name'] = $new_first_name;
            $student_info['last_name'] = $new_last_name;
        } else {
            $error = 'Ошибка при обновлении данных. Возможно, пользователь с таким логином уже существует.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование ученика - QuizCard</title>
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
            padding: 2rem;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
        }

        .content {
            padding: 2rem;
        }

        .student-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }

        .student-info h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }

        .student-info p {
            color: #666;
            margin: 0.25rem 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-right: 1rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .error {
            background: #ffe6e6;
            color: #d00;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #d00;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }

        .password-note {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
            font-size: 0.9rem;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
                gap: 1rem;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🎓 Редактирование ученика</h1>
            <p>Изменение данных доступа и личной информации</p>
        </div>

        <div class="content">
            <div class="student-info">
                <h3>Текущие данные ученика:</h3>
                <p><strong>Имя:</strong> <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?></p>
                <p><strong>Логин:</strong> <?php echo htmlspecialchars($student_info['username']); ?></p>
                <p><strong>Дата регистрации:</strong> <?php echo date('d.m.Y', strtotime($student_info['created_at'])); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">Имя:</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($student_info['first_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Фамилия:</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($student_info['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Логин:</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($student_info['username']); ?>" required>
                </div>

                <div class="password-note">
                    💡 <strong>Изменение пароля:</strong> Оставьте поля пароля пустыми, если не хотите изменять пароль.
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="password">Новый пароль (опционально):</label>
                        <input type="password" id="password" name="password" 
                               placeholder="Оставьте пустым, чтобы не менять">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Подтвердите пароль:</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Повторите новый пароль">
                    </div>
                </div>

                <div class="actions">
                    <a href="students.php" class="btn btn-secondary">← Назад к списку</a>
                    <button type="submit" name="update_student" class="btn btn-primary">💾 Сохранить изменения</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Синхронизация полей пароля
        document.getElementById('password').addEventListener('input', function() {
            const confirmField = document.getElementById('confirm_password');
            if (this.value === '') {
                confirmField.value = '';
                confirmField.disabled = true;
                confirmField.placeholder = 'Поле заблокировано';
            } else {
                confirmField.disabled = false;
                confirmField.placeholder = 'Повторите новый пароль';
            }
        });

        // Автоматическое скрытие сообщений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.error, .success');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
