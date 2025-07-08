<?php
class Vocabulary {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function addWord($deck_id, $foreign_word, $translation, $image_path = null) {
        $query = "INSERT INTO vocabulary (deck_id, foreign_word, translation, image_path) 
                  VALUES (:deck_id, :foreign_word, :translation, :image_path)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':foreign_word', $foreign_word);
        $stmt->bindParam(':translation', $translation);
        $stmt->bindParam(':image_path', $image_path);
        
        if ($stmt->execute()) {
            $vocabulary_id = $this->conn->lastInsertId();
            
            // Создаем записи прогресса для всех учеников, назначенных на эту колоду
            $progress_query = "INSERT INTO learning_progress (student_id, vocabulary_id, next_review_date) 
                             SELECT da.student_id, :vocabulary_id, CURDATE()
                             FROM deck_assignments da 
                             WHERE da.deck_id = :deck_id";
            $progress_stmt = $this->conn->prepare($progress_query);
            $progress_stmt->bindParam(':vocabulary_id', $vocabulary_id);
            $progress_stmt->bindParam(':deck_id', $deck_id);
            $progress_stmt->execute();
            
            return true;
        }
        return false;
    }
    
    public function getVocabularyByDeck($deck_id) {
        $query = "SELECT v.*, COUNT(lp.student_id) as assigned_students
                  FROM vocabulary v
                  LEFT JOIN learning_progress lp ON v.id = lp.vocabulary_id
                  WHERE v.deck_id = :deck_id
                  GROUP BY v.id
                  ORDER BY v.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getVocabularyByStudent($student_id) {
        $query = "SELECT v.*, d.name as deck_name, d.color as deck_color,
                         lp.ease_factor, lp.interval_days, lp.repetition_count, 
                         lp.total_attempts, lp.next_review_date, lp.last_review_date
                  FROM vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  LEFT JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id
                  WHERE da.student_id = :student_id
                  ORDER BY d.name, v.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getWordsForReview($student_id, $deck_id = null, $respect_daily_limit = true) {
        $query = "SELECT v.*, d.name as deck_name, d.color as deck_color, d.daily_word_limit,
                         lp.ease_factor, lp.interval_days, lp.repetition_count, lp.total_attempts,
                         (COALESCE(lp.total_attempts, 0) = 0) as is_new_word
                  FROM vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  INNER JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id
                  WHERE da.student_id = :student_id AND lp.next_review_date <= CURDATE()";
                  
        if ($deck_id) {
            $query .= " AND d.id = :deck_id";
        }
        
        $query .= " ORDER BY lp.next_review_date ASC, lp.repetition_count DESC, RAND()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        if ($deck_id) {
            $stmt->bindParam(':deck_id', $deck_id);
        }
        $stmt->execute();
        
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Если нужно учитывать дневной лимит, фильтруем новые слова
        if ($respect_daily_limit && !empty($words)) {
            require_once __DIR__ . '/Deck.php';
            $deck_obj = new Deck($this->conn);
            
            $filtered_words = [];
            $deck_limits = []; // Кеш лимитов для каждой колоды
            
            foreach ($words as $word) {
                $word_deck_id = $word['deck_id'];
                
                // Если это не новое слово (total_attempts > 0), добавляем без ограничений
                if (($word['total_attempts'] ?? 0) > 0) {
                    $filtered_words[] = $word;
                    continue;
                }
                
                // Для новых слов проверяем дневной лимит
                if (!isset($deck_limits[$word_deck_id])) {
                    $deck_limits[$word_deck_id] = $deck_obj->getDailyLimitInfo($student_id, $word_deck_id);
                }
                
                if ($deck_limits[$word_deck_id]['can_study_more']) {
                    $filtered_words[] = $word;
                }
            }
            
            return $filtered_words;
        }
        
        return $words;
    }
    
    public function updateProgress($student_id, $vocabulary_id, $difficulty) {
        // Алгоритм интервальных повторений на основе SM-2
        $query = "SELECT lp.ease_factor, lp.interval_days, lp.repetition_count, 
                         lp.total_attempts, v.deck_id
                  FROM learning_progress lp
                  INNER JOIN vocabulary v ON lp.vocabulary_id = v.id
                  WHERE lp.student_id = :student_id AND lp.vocabulary_id = :vocabulary_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':vocabulary_id', $vocabulary_id);
        $stmt->execute();
        
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            $ease_factor = $progress['ease_factor'];
            $interval = $progress['interval_days'];
            $repetition = $progress['repetition_count'];
            $total_attempts = $progress['total_attempts'] ?? 0;
            $deck_id = $progress['deck_id'];
            $was_new_word = ($total_attempts == 0); // Используем total_attempts для определения новизны
            
            // Увеличиваем общий счетчик попыток
            $total_attempts++;
            
            if ($difficulty === 'easy') {
                $repetition++;
                if ($repetition == 1) {
                    $interval = 1;
                } elseif ($repetition == 2) {
                    $interval = 6;
                } else {
                    $interval = intval($interval * $ease_factor);
                }
                $ease_factor = $ease_factor + (0.1 - (5 - 4) * (0.08 + (5 - 4) * 0.02));
            } else { // hard
                $repetition = 0;
                $interval = 1;
                $ease_factor = max(1.3, $ease_factor - 0.2);
            }
            
            // Ограничиваем ease_factor
            $ease_factor = max(1.3, min(2.5, $ease_factor));
            
            $next_review = date('Y-m-d', strtotime("+{$interval} days"));
            
            $update_query = "UPDATE learning_progress 
                           SET ease_factor = :ease_factor, 
                               interval_days = :interval_days, 
                               repetition_count = :repetition_count,
                               total_attempts = :total_attempts,
                               next_review_date = :next_review_date,
                               last_review_date = NOW(),
                               difficulty_rating = :difficulty
                           WHERE student_id = :student_id AND vocabulary_id = :vocabulary_id";
            
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':ease_factor', $ease_factor);
            $update_stmt->bindParam(':interval_days', $interval);
            $update_stmt->bindParam(':repetition_count', $repetition);
            $update_stmt->bindParam(':total_attempts', $total_attempts);
            $update_stmt->bindParam(':next_review_date', $next_review);
            $update_stmt->bindParam(':difficulty', $difficulty);
            $update_stmt->bindParam(':student_id', $student_id);
            $update_stmt->bindParam(':vocabulary_id', $vocabulary_id);
            
            $result = $update_stmt->execute();
            
            // Если это было новое слово, обновляем дневной счетчик (независимо от успешности)
            if ($result && $was_new_word) {
                require_once __DIR__ . '/Deck.php';
                $deck_obj = new Deck($this->conn);
                $deck_obj->incrementTodayStudiedCount($student_id, $deck_id);
            }
            
            return $result;
        }
        
        return false;
    }
    
    public function deleteWord($vocabulary_id, $teacher_id) {
        // Проверяем, что слово принадлежит колоде данного преподавателя
        $query = "DELETE v FROM vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  WHERE v.id = :vocabulary_id AND d.teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vocabulary_id', $vocabulary_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function updateWord($vocabulary_id, $foreign_word, $translation, $image_path, $teacher_id) {
        // Проверяем, что слово принадлежит колоде данного преподавателя
        $query = "UPDATE vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  SET v.foreign_word = :foreign_word, 
                      v.translation = :translation, 
                      v.image_path = :image_path
                  WHERE v.id = :vocabulary_id AND d.teacher_id = :teacher_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vocabulary_id', $vocabulary_id);
        $stmt->bindParam(':foreign_word', $foreign_word);
        $stmt->bindParam(':translation', $translation);
        $stmt->bindParam(':image_path', $image_path);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        return $stmt->execute();
    }
    
    public function resetStudentProgress($student_id, $teacher_id) {
        // Сбрасываем прогресс только для слов из колод данного преподавателя
        $query = "DELETE lp FROM learning_progress lp
                  INNER JOIN vocabulary v ON lp.vocabulary_id = v.id
                  INNER JOIN decks d ON v.deck_id = d.id
                  WHERE lp.student_id = :student_id AND d.teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        
        if ($stmt->execute()) {
            // Создаем новые записи прогресса с начальными значениями
            $query = "INSERT INTO learning_progress (student_id, vocabulary_id, next_review_date)
                      SELECT :student_id, v.id, CURDATE()
                      FROM vocabulary v
                      INNER JOIN decks d ON v.deck_id = d.id
                      INNER JOIN deck_assignments da ON d.id = da.deck_id
                      WHERE da.student_id = :student_id AND d.teacher_id = :teacher_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':teacher_id', $teacher_id);
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    public function resetDeckProgress($student_id, $deck_id, $teacher_id) {
        // Проверяем, что колода принадлежит преподавателю
        $query = "SELECT id FROM decks WHERE id = :deck_id AND teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            return false;
        }
        
        // Сбрасываем прогресс для конкретной колоды
        $query = "DELETE lp FROM learning_progress lp
                  INNER JOIN vocabulary v ON lp.vocabulary_id = v.id
                  WHERE lp.student_id = :student_id AND v.deck_id = :deck_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':deck_id', $deck_id);
        
        if ($stmt->execute()) {
            // Создаем новые записи прогресса для этой колоды
            $query = "INSERT INTO learning_progress (student_id, vocabulary_id, next_review_date)
                      SELECT :student_id, v.id, CURDATE()
                      FROM vocabulary v
                      WHERE v.deck_id = :deck_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':deck_id', $deck_id);
            
            return $stmt->execute();
        }
        
        return false;
    }

    public function getStatistics($student_id) {
        $query = "SELECT 
                    COUNT(v.id) as total_words,
                    SUM(CASE WHEN lp.next_review_date <= CURDATE() THEN 1 ELSE 0 END) as words_to_review,
                    AVG(lp.ease_factor) as avg_ease_factor,
                    SUM(lp.repetition_count) as total_repetitions,
                    COUNT(DISTINCT d.id) as total_decks
                  FROM vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  LEFT JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id
                  WHERE da.student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить статистику по дневным лимитам для студента
     */
    public function getDailyLimitStatistics($student_id) {
        $query = "SELECT 
                    d.id,
                    d.name,
                    d.color,
                    d.daily_word_limit,
                    COALESCE(dsl.words_studied, 0) as words_studied_today,
                    (d.daily_word_limit - COALESCE(dsl.words_studied, 0)) as remaining_today
                  FROM decks d
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  LEFT JOIN daily_study_limits dsl ON d.id = dsl.deck_id 
                            AND dsl.student_id = :student_id 
                            AND dsl.study_date = CURDATE()
                  WHERE da.student_id = :student_id
                  ORDER BY d.name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function wordExists($deck_id, $foreign_word) {
        $query = "SELECT COUNT(*) FROM vocabulary WHERE deck_id = :deck_id AND foreign_word = :foreign_word";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':foreign_word', $foreign_word);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    public function getWordCountInDeck($deck_id) {
        $query = "SELECT COUNT(*) FROM vocabulary WHERE deck_id = :deck_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    public function addWordSafe($deck_id, $foreign_word, $translation, $image_path = null) {
        // Проверяем, не существует ли уже такое слово в колоде
        if ($this->wordExists($deck_id, $foreign_word)) {
            return false; // Слово уже существует
        }
        
        return $this->addWord($deck_id, $foreign_word, $translation, $image_path);
    }
    
    /**
     * Получить слова, изученные сегодня для принудительного повторения
     */
    public function getWordsStudiedToday($student_id, $deck_id = null) {
        $query = "SELECT v.*, d.name as deck_name, d.color as deck_color,
                         lp.ease_factor, lp.interval_days, lp.repetition_count, lp.total_attempts
                  FROM vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  INNER JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id
                  WHERE da.student_id = :student_id 
                    AND DATE(lp.last_review_date) = CURDATE()
                    AND lp.last_review_date IS NOT NULL";
                  
        if ($deck_id) {
            $query .= " AND d.id = :deck_id";
        }
        
        $query .= " ORDER BY lp.last_review_date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        if ($deck_id) {
            $stmt->bindParam(':deck_id', $deck_id);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Получить все изученные слова в колоде для принудительного повторения
     */
    public function getAllStudiedWords($student_id, $deck_id = null) {
        $query = "SELECT v.*, d.name as deck_name, d.color as deck_color,
                         lp.ease_factor, lp.interval_days, lp.repetition_count, lp.total_attempts
                  FROM vocabulary v
                  INNER JOIN decks d ON v.deck_id = d.id
                  INNER JOIN deck_assignments da ON d.id = da.deck_id
                  INNER JOIN learning_progress lp ON v.id = lp.vocabulary_id AND lp.student_id = :student_id
                  WHERE da.student_id = :student_id 
                    AND lp.total_attempts > 0";
                  
        if ($deck_id) {
            $query .= " AND d.id = :deck_id";
        }
        
        $query .= " ORDER BY lp.last_review_date DESC, lp.repetition_count ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        if ($deck_id) {
            $stmt->bindParam(':deck_id', $deck_id);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить средний прогресс по колодам для одного ученика
     */
    public function getStudentAverageDeckProgress($student_id) {
        // Рассчитываем "оценку" как процент изученных слов (total_attempts > 0)
        $query = "SELECT 
                    (SUM(CASE WHEN lp.total_attempts > 0 THEN 1 ELSE 0 END) / COUNT(lp.vocabulary_id)) * 100 as average_progress
                  FROM learning_progress lp
                  WHERE lp.student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['average_progress'] ?? 0;
    }
}
?>
