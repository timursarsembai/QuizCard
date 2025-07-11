<?php
// Простой тест регистрации без email верификации
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$message = '';
$error = '';

if ($_POST && isset($_POST['test_register'])) {
    try {
        require_once 'config/database.php';
        require_once 'classes/User.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$database->isConnected()) {
            throw new Exception('DB connection failed: ' . $database->getError());
        }
        
        $user = new User($db);
        
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        
        echo "<h3>Отладочная информация:</h3>";
        echo "Username: " . htmlspecialchars($username) . "<br>";
        echo "Email: " . htmlspecialchars($email) . "<br>";
        echo "First name: " . htmlspecialchars($first_name) . "<br>";
        echo "Last name: " . htmlspecialchars($last_name) . "<br>";
        
        // Проверки
        if (empty($username) || empty($first_name) || empty($last_name)) {
            throw new Exception('Все поля обязательны');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Пароль должен быть минимум 6 символов');
        }
        
        if (empty($email)) {
            throw new Exception('Email обязателен');
        }
        
        if ($user->isUsernameExists($username)) {
            throw new Exception('Пользователь уже существует');
        }
        
        if ($user->isEmailExists($email)) {
            throw new Exception('Email уже используется');
        }
        
        // СТАРЫЙ способ создания (без email верификации)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, role, first_name, last_name, email, email_verified) 
                  VALUES (:username, :password, 'teacher', :first_name, :last_name, :email, 1)";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        
        if ($stmt->execute()) {
            $message = "✅ Пользователь создан успешно! (без email верификации)";
        } else {
            throw new Exception('Не удалось создать пользователя');
        }
        
    } catch (Exception $e) {
        $error = "❌ Ошибка: " . $e->getMessage();
        echo "<pre>Стек вызовов:\n" . $e->getTraceAsString() . "</pre>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Тест регистрации</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🧪 Простой тест регистрации</h1>
    <p>Этот тест создает пользователя БЕЗ email верификации для проверки базовой функциональности.</p>
    
    <?php if ($message): ?>
        <div class="success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Имя пользователя:</label>
            <input type="text" name="username" required>
        </div>
        
        <div class="form-group">
            <label>Пароль:</label>
            <input type="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label>Имя:</label>
            <input type="text" name="first_name" required>
        </div>
        
        <div class="form-group">
            <label>Фамилия:</label>
            <input type="text" name="last_name" required>
        </div>
        
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>
        
        <button type="submit" name="test_register" class="btn">Создать тестового пользователя</button>
    </form>
    
    <hr>
    <p><a href="debug_registration.php">🔍 Диагностика системы</a></p>
    <p><a href="login.php">🏠 Основная форма входа</a></p>
</body>
</html>
