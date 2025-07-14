<?php
namespace App\Models;

class Deck {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function createDeck($teacher_id, $name, $description = null, $color = '#667eea', $daily_word_limit = 20) {
        $query = "INSERT INTO decks (teacher_id, name, description, color, daily_word_limit) 
                  VALUES (:teacher_id, :name, :description, :color, :daily_word_limit)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':daily_word_limit', $daily_word_limit);
        
        return $stmt->execute();
    }
    
    public function getDecksByTeacher($teacher_id) {
        // Используем простой запрос без GROUP BY для получения колод
        $query = "SELECT id, name, COALESCE(description, '') as description, color, 
                         teacher_id, daily_word_limit, created_at 
                  FROM decks 
                  WHERE teacher_id = :teacher_id 
                  ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Используем ассоциативный массив для гарантированного удаления дубликатов
        $unique_decks_map = [];
        
        foreach ($decks as $deck_item) {
            // Используем ID как ключ, чтобы автоматически убрать дубликаты
            if (!isset($unique_decks_map[$deck_item['id']])) {
                // Подсчет слов
                $word_query = "SELECT COUNT(*) as word_count FROM vocabulary WHERE deck_id = :deck_id";
                $word_stmt = $this->conn->prepare($word_query);
                $word_stmt->bindParam(':deck_id', $deck_item['id']);
                $word_stmt->execute();
                $word_result = $word_stmt->fetch(PDO::FETCH_ASSOC);
                $deck_item['word_count'] = (int)$word_result['word_count'];
                
                // Подсчет назначенных студентов
                try {
                    $student_query = "SELECT COUNT(DISTINCT student_id) as assigned_students FROM deck_assignments WHERE deck_id = :deck_id";
                    $student_stmt = $this->conn->prepare($student_query);
                    $student_stmt->bindParam(':deck_id', $deck_item['id']);
                    $student_stmt->execute();
                    $student_result = $student_stmt->fetch(PDO::FETCH_ASSOC);
                    $deck_item['assigned_students'] = (int)$student_result['assigned_students'];
                } catch (PDOException $e) {
                    $deck_item['assigned_students'] = 0;
                }
                
                $unique_decks_map[$deck_item['id']] = $deck_item;
            }
        }
        
        // Преобразуем обратно в индексированный массив, сохраняя порядок
        return array_values($unique_decks_map);
    }
    
    public function getDecksCountByTeacher($teacher_id) {
        $query = "SELECT COUNT(*) FROM decks WHERE teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getWordsCountByTeacher($teacher_id) {
        $query = "SELECT COUNT(*) FROM vocabulary v JOIN decks d ON v.deck_id = d.id WHERE d.teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getDeckById($deck_id, $teacher_id) {
        $query = "SELECT * FROM decks WHERE id = :deck_id AND teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateDeck($deck_id, $teacher_id, $name, $description = null, $color = '#667eea', $daily_word_limit = 20) {
        $query = "UPDATE decks SET name = :name, description = :description, color = :color, daily_word_limit = :daily_word_limit 
                  WHERE id = :deck_id AND teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':daily_word_limit', $daily_word_limit);
        
        return $stmt->execute();
    }
    
    public function deleteDeck($deck_id, $teacher_id) {
        $query = "DELETE FROM decks WHERE id = :deck_id AND teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function assignDeckToStudent($deck_id, $student_id, $teacher_id) {
        // Проверяем, что колода принадлежит преподавателю
        $deck = $this->getDeckById($deck_id, $teacher_id);
        if (!$deck) {
            return false;
        }
        
        $query = "INSERT IGNORE INTO deck_assignments (deck_id, student_id) VALUES (:deck_id, :student_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':student_id', $student_id);
        
        if ($stmt->execute()) {
            // Создаем записи прогресса для всех слов в колоде
            $this->createProgressForDeck($deck_id, $student_id);
            return true;
        }
        
        return false;
    }
    
    public function unassignDeckFromStudent($deck_id, $student_id, $teacher_id) {
        // Проверяем, что колода принадлежит преподавателю
        $deck = $this->getDeckById($deck_id, $teacher_id);
        if (!$deck) {
            return false;
        }
        
        $query = "DELETE FROM deck_assignments WHERE deck_id = :deck_id AND student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':student_id', $student_id);
        
        return $stmt->execute();
    }
    
    public function getStudentsForDeck($deck_id, $teacher_id) {
        $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.created_at,
                         da.assigned_at
                  FROM users u
                  INNER JOIN deck_assignments da ON u.id = da.student_id
                  INNER JOIN decks d ON da.deck_id = d.id
                  WHERE da.deck_id = :deck_id AND d.teacher_id = :teacher_id AND u.role = 'student'
                  ORDER BY u.last_name, u.first_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableStudents($deck_id, $teacher_id) {
        $query = "SELECT u.id, u.username, u.first_name, u.last_name
                  FROM users u
                  WHERE u.teacher_id = :teacher_id 
                    AND u.role = 'student'
                    AND u.id NOT IN (
                        SELECT da.student_id 
                        FROM deck_assignments da 
                        INNER JOIN decks d ON da.deck_id = d.id
                        WHERE da.deck_id = :deck_id AND d.teacher_id = :teacher_id
                    )
                  ORDER BY u.last_name, u.first_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDecksForStudent($student_id) {
        $query = "SELECT d.*, da.assigned_at,
                         COUNT(v.id) as total_words,
                         COUNT(CASE WHEN lp.next_review_date <= CURDATE() THEN 1 END) as words_to_review
                  FROM decks d
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  LEFT JOIN vocabulary v ON d.id = v.deck_id
                  LEFT JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id1
                  WHERE da.student_id = :student_id2
                  GROUP BY d.id
                  ORDER BY da.assigned_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id1', $student_id);
        $stmt->bindParam(':student_id2', $student_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStudentDeckStats($student_id, $teacher_id) {
        $query = "SELECT d.id, d.name, d.color,
                         COUNT(v.id) as total_words,
                         COUNT(CASE WHEN lp.next_review_date <= CURDATE() THEN 1 END) as words_to_review,
                         COUNT(CASE WHEN lp.repetition_count >= 3 THEN 1 END) as learned_words
                  FROM decks d
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  LEFT JOIN vocabulary v ON d.id = v.deck_id
                  LEFT JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id1
                  WHERE da.student_id = :student_id2 AND d.teacher_id = :teacher_id
                  GROUP BY d.id
                  ORDER BY d.name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id1', $student_id);
        $stmt->bindParam(':student_id2', $student_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function createProgressForDeck($deck_id, $student_id) {
        $query = "INSERT IGNORE INTO learning_progress (student_id, vocabulary_id, next_review_date)
                  SELECT :student_id, v.id, CURDATE()
                  FROM vocabulary v 
                  WHERE v.deck_id = :deck_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':deck_id', $deck_id);
        
        return $stmt->execute();
    }
    
    public function getDeckByIdAny($deck_id) {
        // Метод для получения колоды без проверки teacher_id (для диагностики)
        $query = "SELECT * FROM decks WHERE id = :deck_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить текущий лимит изученных слов студента за сегодня для конкретной колоды
     */
    public function getTodayStudiedCount($student_id, $deck_id) {
        $query = "SELECT words_studied FROM daily_study_limits 
                  WHERE student_id = :student_id AND deck_id = :deck_id AND study_date = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['words_studied'] : 0;
    }
    
    /**
     * Увеличить счетчик изученных слов за сегодня
     */
    public function incrementTodayStudiedCount($student_id, $deck_id) {
        $query = "INSERT INTO daily_study_limits (student_id, deck_id, study_date, words_studied) 
                  VALUES (:student_id, :deck_id, CURDATE(), 1)
                  ON DUPLICATE KEY UPDATE words_studied = words_studied + 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':deck_id', $deck_id);
        
        return $stmt->execute();
    }
    
    /**
     * Проверить, можно ли изучать новые слова из данной колоды сегодня
     */
    public function canStudyMoreToday($student_id, $deck_id) {
        // Получаем лимит колоды
        $deck = $this->getDeckByIdAny($deck_id);
        if (!$deck) return false;
        
        $daily_limit = $deck['daily_word_limit'];
        $studied_today = $this->getTodayStudiedCount($student_id, $deck_id);
        
        return $studied_today < $daily_limit;
    }
    
    /**
     * Получить информацию о дневном лимите для колоды
     */
    public function getDailyLimitInfo($student_id, $deck_id) {
        $deck = $this->getDeckByIdAny($deck_id);
        if (!$deck) return null;
        
        return [
            'daily_limit' => $deck['daily_word_limit'],
            'studied_today' => $this->getTodayStudiedCount($student_id, $deck_id),
            'can_study_more' => $this->canStudyMoreToday($student_id, $deck_id)
        ];
    }
    
    /**
     * Получить всех назначенных студентов для колоды
     */
    public function getAssignedStudents($deck_id) {
        $query = "SELECT u.id, u.username, u.first_name, u.last_name
                  FROM users u
                  INNER JOIN deck_assignments da ON u.id = da.student_id
                  WHERE da.deck_id = :deck_id AND u.role = 'student'
                  ORDER BY u.last_name, u.first_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Назначить студента на колоду
     */
    public function assignStudentToDeck($deck_id, $student_id) {
        $query = "INSERT IGNORE INTO deck_assignments (deck_id, student_id) VALUES (:deck_id, :student_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':student_id', $student_id);
        
        if ($stmt->execute()) {
            // Создаем записи прогресса для всех слов в колоде
            $this->createProgressForDeck($deck_id, $student_id);
            return true;
        }
        
        return false;
    }
    
    /**
     * Удалить студента с колоды
     */
    public function removeStudentFromDeck($deck_id, $student_id) {
        $query = "DELETE FROM deck_assignments WHERE deck_id = :deck_id AND student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':student_id', $student_id);
        
        return $stmt->execute();
    }
    
    /**
     * Простой метод получения колод без JOIN (для диагностики)
     */
    public function getDecksByTeacherSimple($teacher_id) {
        $query = "SELECT DISTINCT * FROM decks WHERE teacher_id = :teacher_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        $decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Убираем возможные дубликаты по ID
        $unique_decks = [];
        $seen_ids = [];
        
        foreach ($decks as $deck) {
            if (!in_array($deck['id'], $seen_ids)) {
                $seen_ids[] = $deck['id'];
                $unique_decks[] = $deck;
            }
        }
        
        // Добавляем количество слов и студентов отдельными запросами
        foreach ($unique_decks as &$deck) {
            // Подсчет слов
            $word_query = "SELECT COUNT(*) as word_count FROM vocabulary WHERE deck_id = :deck_id";
            $word_stmt = $this->conn->prepare($word_query);
            $word_stmt->bindParam(':deck_id', $deck['id']);
            $word_stmt->execute();
            $word_result = $word_stmt->fetch(PDO::FETCH_ASSOC);
            $deck['word_count'] = $word_result['word_count'];
            
            // Подсчет назначенных студентов
            try {
                $student_query = "SELECT COUNT(DISTINCT student_id) as assigned_students FROM deck_assignments WHERE deck_id = :deck_id";
                $student_stmt = $this->conn->prepare($student_query);
                $student_stmt->bindParam(':deck_id', $deck['id']);
                $student_stmt->execute();
                $student_result = $student_stmt->fetch(PDO::FETCH_ASSOC);
                $deck['assigned_students'] = $student_result['assigned_students'];
            } catch (PDOException $e) {
                // Если таблица deck_assignments не существует или есть другая ошибка
                $deck['assigned_students'] = 0;
            }
        }
        
        return $unique_decks;
    }
    
    /**
     * Метод для отладки - получить все колоды с подробной информацией
     */
    public function debugGetDecksByTeacher($teacher_id) {
        $query = "SELECT * FROM decks WHERE teacher_id = :teacher_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        $raw_decks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        echo "Raw decks from database (teacher_id: $teacher_id):\n";
        echo "Count: " . count($raw_decks) . "\n";
        foreach ($raw_decks as $deck) {
            echo "ID: {$deck['id']}, Name: {$deck['name']}, Created: {$deck['created_at']}\n";
        }
        echo "</pre>";
        
        return $raw_decks;
    }
}
