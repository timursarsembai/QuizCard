<?php
// Front Controller для MVC
// Инициализация окружения, автозагрузка, роутинг

require_once __DIR__ . '/../vendor/autoload.php';
use App\Controllers\Router;

$uri = $_SERVER['REQUEST_URI'];
Router::route($uri);

// ...existing code...
