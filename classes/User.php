<?php
class User {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, password, role, first_name, last_name, teacher_id 
                  FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['teacher_id'] = $row['teacher_id'];
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function getRole() {
        return $_SESSION['role'] ?? null;
    }
    
    public function createTeacher($username, $password, $first_name, $last_name, $email = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, role, first_name, last_name, email) 
                  VALUES (:username, :password, 'teacher', :first_name, :last_name, :email)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            // Обрабатываем ошибку дублирования имени пользователя
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    public function createStudent($username, $password, $first_name, $last_name, $teacher_id) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, role, first_name, last_name, teacher_id) 
                  VALUES (:username, :password, 'student', :first_name, :last_name, :teacher_id)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function getStudentsByTeacher($teacher_id) {
        $query = "SELECT id, username, first_name, last_name, created_at 
                  FROM users WHERE teacher_id = :teacher_id AND role = 'student'
                  ORDER BY last_name, first_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStudentsCountByTeacher($teacher_id) {
        $query = "SELECT COUNT(*) FROM users WHERE teacher_id = :teacher_id AND role = 'student'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    public function getStudentInfo($student_id, $teacher_id) {
        $query = "SELECT id, username, first_name, last_name, created_at 
                  FROM users 
                  WHERE id = :student_id AND teacher_id = :teacher_id AND role = 'student'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteStudent($student_id, $teacher_id) {
        $query = "DELETE FROM users WHERE id = :student_id AND teacher_id = :teacher_id AND role = 'student'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function updateStudent($student_id, $teacher_id, $username = null, $password = null, $first_name = null, $last_name = null) {
        // Проверяем, что ученик принадлежит этому преподавателю
        $check_query = "SELECT id FROM users WHERE id = :student_id AND teacher_id = :teacher_id AND role = 'student'";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':student_id', $student_id);
        $check_stmt->bindParam(':teacher_id', $teacher_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            return false; // Ученик не найден или не принадлежит преподавателю
        }
        
        // Формируем динамический запрос обновления
        $updates = [];
        $params = [':student_id' => $student_id];
        
        if ($username !== null) {
            $updates[] = "username = :username";
            $params[':username'] = $username;
        }
        
        if ($password !== null) {
            $updates[] = "password = :password";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        if ($first_name !== null) {
            $updates[] = "first_name = :first_name";
            $params[':first_name'] = $first_name;
        }
        
        if ($last_name !== null) {
            $updates[] = "last_name = :last_name";
            $params[':last_name'] = $last_name;
        }
        
        if (empty($updates)) {
            return true; // Нечего обновлять
        }
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :student_id";
        $stmt = $this->conn->prepare($query);
        
        try {
            return $stmt->execute($params);
        } catch (PDOException $e) {
            // Обрабатываем ошибку дублирования имени пользователя
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }
}
?>
