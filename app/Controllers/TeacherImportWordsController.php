<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Deck;

class TeacherImportWordsController 
{
    private $db;
    private $user;
    private $vocabulary;
    private $deck;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->vocabulary = new Vocabulary($db);
        $this->deck = new Deck($db);
    }
    
    public function index() 
    {
        // Включаем отображение всех ошибок для отладки
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        session_start();

        // Проверяем подключение к БД
        if (!$this->db) {
            $error = 'Ошибка подключения к базе данных';
        }

        // Проверяем авторизацию
        if ($this->db && (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher')) {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];
        $success = null;
        $error = null;

        // Обработка импорта CSV файла
        if ($_POST && isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
            $deck_id = $_POST['deck_id'];
            
            if ($deck_id && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $result = $this->vocabulary->importFromCSV($_FILES['csv_file']['tmp_name'], $deck_id, $teacher_id);
                
                if ($result['success']) {
                    $success = "Импортировано слов: " . $result['imported_count'];
                } else {
                    $error = $result['error'];
                }
            } else {
                $error = "Выберите файл для импорта";
            }
        }

        // Получаем колоды преподавателя
        $teacher_decks = $this->deck->getDecksByTeacher($teacher_id);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/import_words.php';
    }
}
