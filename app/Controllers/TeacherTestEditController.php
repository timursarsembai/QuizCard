<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;
use App\Models\Vocabulary;

class TeacherTestEditController 
{
    private $db;
    private $user;
    private $deck;
    private $test;
    private $vocabulary;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->deck = new Deck($db);
        $this->test = new Test($db);
        $this->vocabulary = new Vocabulary($db);
    }
    
    public function edit() 
    {
        session_start();

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];
        $success = null;
        $error = null;

        // Проверяем test_id
        if (!isset($_GET['test_id'])) {
            header("Location: /teacher/decks");
            exit();
        }

        $test_id = $_GET['test_id'];
        $current_test = $this->test->getTestById($test_id);

        if (!$current_test) {
            header("Location: /teacher/decks");
            exit();
        }

        // Проверяем, что тест принадлежит преподавателю
        $current_deck = $this->deck->getDeckById($current_test['deck_id'], $teacher_id);
        if (!$current_deck) {
            header("Location: /teacher/decks");
            exit();
        }

        // Обработка сохранения теста
        if ($_POST && isset($_POST['save_test'])) {
            $test_name = trim($_POST['test_name']);
            $time_limit = intval($_POST['time_limit']) ?: null;
            
            // Обновляем основную информацию теста
            if ($this->test->updateTestInfo($test_id, $test_name, $time_limit)) {
                // Обрабатываем вопросы
                $questions_data = [];
                if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                    foreach ($_POST['questions'] as $q_data) {
                        if (!empty($q_data['question']) && !empty($q_data['correct_answer'])) {
                            $questions_data[] = [
                                'question' => trim($q_data['question']),
                                'option_a' => trim($q_data['option_a']),
                                'option_b' => trim($q_data['option_b']),
                                'option_c' => trim($q_data['option_c']),
                                'option_d' => trim($q_data['option_d']),
                                'correct_answer' => $q_data['correct_answer']
                            ];
                        }
                    }
                }
                
                if (!empty($questions_data)) {
                    if ($this->test->updateTestQuestions($test_id, $questions_data)) {
                        $success = "Тест успешно сохранен!";
                        // Обновляем данные теста
                        $current_test = $this->test->getTestById($test_id);
                    } else {
                        $error = "Ошибка при сохранении вопросов";
                    }
                } else {
                    $error = "Добавьте хотя бы один вопрос";
                }
            } else {
                $error = "Ошибка при обновлении теста";
            }
        }

        // Обработка генерации вопросов
        if ($_POST && isset($_POST['generate_questions'])) {
            $questions_count = intval($_POST['questions_count']);
            if ($questions_count > 0) {
                if ($this->test->generateQuestionsForTest($test_id, $questions_count)) {
                    $success = "Вопросы успешно сгенерированы!";
                    // Обновляем данные теста
                    $current_test = $this->test->getTestById($test_id);
                } else {
                    $error = "Ошибка при генерации вопросов";
                }
            }
        }

        // Получаем вопросы теста
        $questions = $this->test->getTestQuestions($test_id);
        $words = $this->vocabulary->getVocabularyByDeck($current_test['deck_id']);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/test_edit.php';
    }
}
