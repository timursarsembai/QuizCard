<?php
session_start();

// Сохраняем роль пользователя перед очисткой сессии
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Очищаем все данные сессии
session_unset();
session_destroy();

// Перенаправляем на соответствующую страницу входа
if ($user_role === 'student') {
    header("Location: student_login.php");
} else {
    header("Location: index.php");
}
exit();
?>
