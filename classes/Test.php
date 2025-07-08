<?php
class Test {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Удалить тест
     */
    public function deleteTest($test_id, $teacher_id) {
        // Проверяем, принадлежит ли тест преподавателю
        $query = "SELECT t.id FROM tests t 
                  JOIN decks d ON t.deck_id = d.id 
                  WHERE t.id = :test_id AND d.teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            return false;
        }

        // Удаляем ответы
        $query = "DELETE FROM test_answers WHERE attempt_id IN 
                  (SELECT id FROM test_attempts WHERE test_id = :test_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();

        // Удаляем попытки
        $query = "DELETE FROM test_attempts WHERE test_id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();

        // Удаляем вопросы
        $query = "DELETE FROM test_questions WHERE test_id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();

        // Удаляем тест
        $query = "DELETE FROM tests WHERE id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        
        return $stmt->execute();
    }
    
    /**
     * Создать новый тест
     */
    public function createTest($deck_id, $name, $questions_count, $time_limit = null) {
        $query = "INSERT INTO tests (deck_id, name, questions_count, time_limit, created_at) 
                  VALUES (:deck_id, :name, :questions_count, :time_limit, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':questions_count', $questions_count);
        $stmt->bindParam(':time_limit', $time_limit);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Получить тест по ID
     */
    public function getTestById($test_id) {
        $query = "SELECT t.*, d.name as deck_name 
                  FROM tests t 
                  LEFT JOIN decks d ON t.deck_id = d.id 
                  WHERE t.id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получить все тесты для колоды
     */
    public function getTestsByDeck($deck_id) {
        $query = "SELECT t.*, 
                  (SELECT COUNT(*) FROM test_attempts ta WHERE ta.test_id = t.id) as attempts_count
                  FROM tests t 
                  WHERE t.deck_id = :deck_id 
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $deck_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить все тесты преподавателя
     */
    public function getTestsByTeacher($teacher_id) {
        $query = "SELECT t.*, d.name as deck_name, d.color as deck_color,
                  (SELECT COUNT(*) FROM test_attempts ta WHERE ta.test_id = t.id) as attempts_count,
                  (SELECT COUNT(DISTINCT ta.student_id) FROM test_attempts ta WHERE ta.test_id = t.id) as unique_students,
                  (SELECT AVG(ta.score) FROM test_attempts ta WHERE ta.test_id = t.id) as avg_score
                  FROM tests t 
                  JOIN decks d ON t.deck_id = d.id
                  WHERE d.teacher_id = :teacher_id 
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTestsCountByTeacher($teacher_id) {
        $query = "SELECT COUNT(*) FROM tests t JOIN decks d ON t.deck_id = d.id WHERE d.teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getRecentTestAttemptsByTeacher($teacher_id, $limit = 5) {
        $query = "SELECT ta.score, ta.completed_at, t.name as test_name, u.username as student_name
                  FROM test_attempts ta
                  JOIN tests t ON ta.test_id = t.id
                  JOIN users u ON ta.student_id = u.id
                  JOIN decks d ON t.deck_id = d.id
                  WHERE d.teacher_id = :teacher_id
                  ORDER BY ta.completed_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Обновить информацию о тесте
     */
    public function updateTestInfo($test_id, $name, $time_limit = null) {
        $query = "UPDATE tests SET name = :name, time_limit = :time_limit WHERE id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':time_limit', $time_limit);
        
        return $stmt->execute();
    }

    /**
     * Генерировать вопросы для теста
     */
    public function generateQuestionsForTest($test_id, $questions_count) {
        // Получаем колоду теста
        $test = $this->getTestById($test_id);
        if (!$test) return false;

        // Получаем слова из колоды
        $query = "SELECT * FROM vocabulary WHERE deck_id = :deck_id ORDER BY RAND() LIMIT :questions_count";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $test['deck_id']);
        $stmt->bindParam(':questions_count', $questions_count, PDO::PARAM_INT);
        $stmt->execute();
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($words)) return false;

        // Получаем все переводы для создания неверных вариантов
        $query = "SELECT translation FROM vocabulary WHERE deck_id = :deck_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':deck_id', $test['deck_id']);
        $stmt->execute();
        $all_translations = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'translation');

        // Удаляем существующие вопросы
        $query = "DELETE FROM test_questions WHERE test_id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();

        // Создаем вопросы
        foreach ($words as $word) {
            // Генерируем 3 неверных варианта
            $wrong_answers = array_diff($all_translations, [$word['translation']]);
            shuffle($wrong_answers);
            $wrong_answers = array_slice($wrong_answers, 0, 3);

            // Создаем все 4 варианта
            $options = array_merge([$word['translation']], $wrong_answers);
            shuffle($options);

            // Находим правильный ответ
            $correct_answer = chr(65 + array_search($word['translation'], $options)); // A, B, C, D

            $question = "Как переводится слово: " . $word['foreign_word'] . "?";

            $query = "INSERT INTO test_questions (test_id, question, option_a, option_b, option_c, option_d, correct_answer) 
                      VALUES (:test_id, :question, :option_a, :option_b, :option_c, :option_d, :correct_answer)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':test_id', $test_id);
            $stmt->bindParam(':question', $question);
            $stmt->bindParam(':option_a', $options[0]);
            $stmt->bindParam(':option_b', $options[1]);
            $stmt->bindParam(':option_c', $options[2]);
            $stmt->bindParam(':option_d', $options[3]);
            $stmt->bindParam(':correct_answer', $correct_answer);
            $stmt->execute();
        }

        return true;
    }

    /**
     * Получить вопросы теста
     */
    public function getTestQuestions($test_id) {
        $query = "SELECT * FROM test_questions WHERE test_id = :test_id ORDER BY id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Обновить вопросы теста
     */
    public function updateTestQuestions($test_id, $questions_data) {
        // Удаляем существующие вопросы
        $query = "DELETE FROM test_questions WHERE test_id = :test_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();

        // Добавляем новые вопросы
        foreach ($questions_data as $question_data) {
            $query = "INSERT INTO test_questions (test_id, question, option_a, option_b, option_c, option_d, correct_answer) 
                      VALUES (:test_id, :question, :option_a, :option_b, :option_c, :option_d, :correct_answer)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':test_id', $test_id);
            $stmt->bindParam(':question', $question_data['question']);
            $stmt->bindParam(':option_a', $question_data['option_a']);
            $stmt->bindParam(':option_b', $question_data['option_b']);
            $stmt->bindParam(':option_c', $question_data['option_c']);
            $stmt->bindParam(':option_d', $question_data['option_d']);
            $stmt->bindParam(':correct_answer', $question_data['correct_answer']);
            
            if (!$stmt->execute()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Сохранить попытку прохождения теста
     */
    public function saveTestAttempt($test_id, $student_id, $answers, $time_spent) {
        // Получаем вопросы теста
        $questions = $this->getTestQuestions($test_id);
        
        $correct_answers = 0;
        $total_questions = count($questions);

        // Подсчитываем правильные ответы
        foreach ($questions as $question) {
            if (isset($answers[$question['id']]) && $answers[$question['id']] === $question['correct_answer']) {
                $correct_answers++;
            }
        }

        $score = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;

        // Сохраняем попытку
        $query = "INSERT INTO test_attempts (test_id, student_id, score, correct_answers, total_questions, time_spent, completed_at) 
                  VALUES (:test_id, :student_id, :score, :correct_answers, :total_questions, :time_spent, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':score', $score);
        $stmt->bindParam(':correct_answers', $correct_answers);
        $stmt->bindParam(':total_questions', $total_questions);
        $stmt->bindParam(':time_spent', $time_spent);
        
        if ($stmt->execute()) {
            $attempt_id = $this->conn->lastInsertId();

            // Сохраняем ответы
            foreach ($answers as $question_id => $answer) {
                $query = "INSERT INTO test_answers (attempt_id, question_id, selected_answer) 
                          VALUES (:attempt_id, :question_id, :selected_answer)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':attempt_id', $attempt_id);
                $stmt->bindParam(':question_id', $question_id);
                $stmt->bindParam(':selected_answer', $answer);
                $stmt->execute();
            }

            return $attempt_id;
        }

        return false;
    }

    /**
     * Получить результат попытки теста
     */
    public function getTestAttemptResult($attempt_id, $student_id) {
        $query = "SELECT ta.*, t.name as test_name,
                  (ta.total_questions - ta.correct_answers) as incorrect_answers
                  FROM test_attempts ta 
                  JOIN tests t ON ta.test_id = t.id 
                  WHERE ta.id = :attempt_id AND ta.student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attempt_id', $attempt_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получить ответы попытки теста
     */
    public function getTestAttemptAnswers($attempt_id) {
        $query = "SELECT * FROM test_answers WHERE attempt_id = :attempt_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':attempt_id', $attempt_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить статистику по тестам для ученика
     */
    public function getStudentTestStatistics($student_id) {
        $query = "SELECT 
                  COUNT(*) as total_attempts,
                  AVG(score) as average_score,
                  MAX(score) as best_score
                  FROM test_attempts 
                  WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получить результаты ученика по конкретному тесту
     */
    public function getStudentTestResults($student_id, $test_id) {
        $query = "SELECT * FROM test_attempts 
                  WHERE student_id = :student_id AND test_id = :test_id 
                  ORDER BY completed_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Проверить доступ студента к тесту
     */
    public function checkStudentTestAccess($test_id, $student_id) {
        $query = "SELECT t.id FROM tests t 
                  JOIN decks d ON t.deck_id = d.id 
                  JOIN deck_assignments da ON d.id = da.deck_id 
                  WHERE t.id = :test_id AND da.student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    /**
     * Получить статистику ученика по конкретному тесту
     */
    public function getStudentTestStats($test_id, $student_id) {
        $query = "SELECT 
                  COUNT(*) as attempts_count,
                  MAX(score) as best_score,
                  AVG(score) as average_score,
                  MAX(completed_at) as last_attempt
                  FROM test_attempts 
                  WHERE test_id = :test_id AND student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':test_id', $test_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Если нет попыток, возвращаем структуру с нулевыми значениями
        if ($result['attempts_count'] == 0) {
            $result['best_score'] = null;
            $result['average_score'] = null;
            $result['last_attempt'] = null;
        }
        
        return $result;
    }

    /**
     * Получить последние попытки ученика
     */
    public function getStudentRecentAttempts($student_id, $limit = 5) {
        $query = "SELECT ta.*, t.name as test_name, d.name as deck_name
                  FROM test_attempts ta 
                  JOIN tests t ON ta.test_id = t.id 
                  JOIN decks d ON t.deck_id = d.id
                  WHERE ta.student_id = :student_id 
                  ORDER BY ta.completed_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получить средний балл по тестам для одного ученика
     */
    public function getStudentAverageTestScore($student_id) {
        $query = "SELECT AVG(score) as average_score FROM test_attempts WHERE student_id = :student_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['average_score'] ?? 0;
    }

    /**
     * Сбросить прогресс ученика по тестам (для преподавателя)
     */
    public function resetStudentTestProgress($student_id, $teacher_id) {
        // Получаем все тесты преподавателя
        $query = "SELECT t.id FROM tests t 
                  JOIN decks d ON t.deck_id = d.id 
                  WHERE d.teacher_id = :teacher_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        $test_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($test_ids)) {
            return true; // Нет тестов для удаления
        }

        // Преобразуем массив ID в строку для IN запроса
        $placeholders = str_repeat('?,', count($test_ids) - 1) . '?';

        // Удаляем ответы ученика
        $query = "DELETE FROM test_answers WHERE attempt_id IN 
                  (SELECT id FROM test_attempts WHERE student_id = ? AND test_id IN ($placeholders))";
        $stmt = $this->conn->prepare($query);
        $params = array_merge([$student_id], $test_ids);
        $stmt->execute($params);

        // Удаляем попытки ученика
        $query = "DELETE FROM test_attempts WHERE student_id = ? AND test_id IN ($placeholders)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return true;
    }
}
?>
