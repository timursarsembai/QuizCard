<?php
// Конфигурация базы данных
class Database {
    private $host = 'localhost';
    private $db_name = 'ramazang_quiz';
    private $username = 'ramazang_qusr';
    private $password = '3UaTq%Gqidx3ok0?';
    public $conn;
    private $error;

    public function getConnection() {
        $this->conn = null;
        $this->error = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            $this->error = $exception->getMessage();
            // В production режиме не показывать детали ошибки
            error_log("Database connection error: " . $exception->getMessage());
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
