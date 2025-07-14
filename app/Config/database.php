<?php
// Конфигурация базы данных
namespace App\Config;

use App\Models\EnvLoader;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;
    private $error;

    public function __construct() {
        // Загружаем переменные окружения
        EnvLoader::load();
        // Инициализируем параметры из переменных окружения
        $this->host = EnvLoader::get('DB_HOST', 'localhost');
        $this->db_name = EnvLoader::get('DB_NAME', 'ramazang_quiz');
        $this->username = EnvLoader::get('DB_USERNAME', 'ramazang_qusr');
        $this->password = EnvLoader::get('DB_PASSWORD', '3UaTq%Gqidx3ok0?');
        // Проверяем обязательные переменные
        try {
            EnvLoader::requireVars(['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD']);
        } catch (RuntimeException $e) {
            $this->error = 'Ошибка конфигурации: ' . $e->getMessage();
            error_log($this->error);
        }
    }

    public function getConnection() {
        $this->conn = null;
        $this->error = null;
        // Если уже есть ошибка конфигурации, не пытаемся подключиться
        if ($this->error !== null) {
            return null;
        }
        try {
            // Настройки подключения для безопасности
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new \PDO($dsn, $this->username, $this->password, $options);
            // Дополнительные настройки безопасности
            $this->conn->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        } catch(\PDOException $exception) {
            $this->error = $exception->getMessage();
            // Логируем ошибку подключения
            require_once dirname(__DIR__) . '/Models/SecurityLogger.php';
            \App\Models\SecurityLogger::logSecurityError('Database connection failed', [
                'error_message' => $exception->getMessage(),
                'db_host' => $this->host,
                'db_name' => $this->db_name
            ]);
            // В production режиме не показывать детали ошибки
            if (EnvLoader::get('APP_ENV', 'production') === 'production') {
                error_log("Database connection error: " . $exception->getMessage());
            }
        }
        return $this->conn;
    }

    public function getError() {
        return $this->error;
    }

    public function isConnected() {
        return $this->conn !== null;
    }
}
?>
