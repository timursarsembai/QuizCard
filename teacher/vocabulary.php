<?php
session_start();
require_once '../config/database.php';
require_once '../config/upload_config.php';
require_once '../config/audio_config.php';
require_once '../classes/User.php';
require_once '../classes/Vocabulary.php';
require_once '../classes/Deck.php';
require_once '../classes/AudioProcessor.php';
require_once '../includes/translations.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$vocabulary = new Vocabulary($db);
$deck = new Deck($db);
$audioProcessor = new AudioProcessor();

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$success = null;
$error = null;

// Проверяем, что deck_id принадлежит данному преподавателю
if (!isset($_GET['deck_id'])) {
    header("Location: decks.php");
    exit();
}

$deck_id = $_GET['deck_id'];
$current_deck = $deck->getDeckById($deck_id, $teacher_id);

if (!$current_deck) {
    header("Location: decks.php");
    exit();
}

// Обработка редактирования колоды
if ($_POST && isset($_POST['edit_deck'])) {
    $name = trim($_POST['deck_name']);
    $description = trim($_POST['deck_description']);
    $color = $_POST['deck_color'] ?: '#667eea';
    $daily_word_limit = intval($_POST['daily_word_limit']) ?: 20;
    
    if ($deck->updateDeck($deck_id, $teacher_id, $name, $description, $color, $daily_word_limit)) {
        $success = "Колода успешно обновлена!";
        // Обновляем данные колоды для отображения
        $current_deck = $deck->getDeckById($deck_id, $teacher_id);
    } else {
        $error = "Ошибка при обновлении колоды";
    }
}

// Обработка назначения/удаления учеников для колоды
if ($_POST && isset($_POST['update_students'])) {
    $selected_students = $_POST['students'] ?? [];
    
    // Получаем текущих назначенных учеников
    $current_students = $deck->getAssignedStudents($deck_id);
    $current_student_ids = array_column($current_students, 'id');
    
    // Удаляем учеников, которые больше не выбраны
    foreach ($current_student_ids as $student_id) {
        if (!in_array($student_id, $selected_students)) {
            $deck->removeStudentFromDeck($deck_id, $student_id);
        }
    }
    
    // Добавляем новых учеников
    foreach ($selected_students as $student_id) {
        if (!in_array($student_id, $current_student_ids)) {
            $deck->assignStudentToDeck($deck_id, $student_id);
        }
    }
    
    $success = "Список учеников успешно обновлен!";
}

// Обработка добавления нового слова
if ($_POST && isset($_POST['add_word'])) {
    $foreign_word = trim($_POST['foreign_word']);
    $translation = trim($_POST['translation']);
    $image_path = null;
    $audio_path = null;
    
    // Обработка загрузки изображения
    if ($_FILES['image']['size'] > 0) {
        // Валидация изображения
        $validation_errors = UploadConfig::validateVocabularyImage($_FILES['image']);
        
        if (!empty($validation_errors)) {
            $error = implode('<br>', $validation_errors);
        } else {
            $upload_dir = UploadConfig::VOCABULARY_UPLOAD_DIR;
            
            if (UploadConfig::ensureUploadDirectory($upload_dir)) {
                $new_filename = UploadConfig::generateVocabularyImageFilename($_FILES['image']['name']);
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = UploadConfig::VOCABULARY_UPLOAD_PATH . $new_filename;
                } else {
                    $error = "Ошибка при загрузке изображения.";
                }
            } else {
                $error = "Ошибка при создании директории для загрузки.";
            }
        }
    }
    
    // Обработка загрузки аудиофайла
    if (!$error && $_FILES['audio']['size'] > 0) {
        $audio_result = $audioProcessor->processAudioUpload($_FILES['audio']);
        
        if (!$audio_result['success']) {
            $error = implode('<br>', $audio_result['errors']);
        } else {
            $audio_path = $audio_result['audio_path'];
        }
    }
    
    // Добавляем слово только если нет ошибок
    if (!$error) {
        if ($vocabulary->addWord($deck_id, $foreign_word, $translation, $image_path, $audio_path)) {
            $success = "Слово успешно добавлено!";
        } else {
            $error = "Ошибка при добавлении слова";
        }
    }
}

// Обработка редактирования слова
if ($_POST && isset($_POST['edit_word'])) {
    $word_id = intval($_POST['word_id']);
    $foreign_word = trim($_POST['foreign_word']);
    $translation = trim($_POST['translation']);
    $current_image = $_POST['current_image'] ?? '';
    $current_audio = $_POST['current_audio'] ?? '';
    $image_path = $current_image; // По умолчанию оставляем текущее изображение
    $audio_path = $current_audio; // По умолчанию оставляем текущее аудио
    
    // Обработка загрузки нового изображения
    if ($_FILES['image']['size'] > 0) {
        // Валидация изображения
        $validation_errors = UploadConfig::validateVocabularyImage($_FILES['image']);
        
        if (!empty($validation_errors)) {
            $error = implode('<br>', $validation_errors);
        } else {
            $upload_dir = UploadConfig::VOCABULARY_UPLOAD_DIR;
            
            if (UploadConfig::ensureUploadDirectory($upload_dir)) {
                $new_filename = UploadConfig::generateVocabularyImageFilename($_FILES['image']['name']);
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Удаляем старое изображение если оно было
                    UploadConfig::deleteOldImage($current_image);
                    $image_path = UploadConfig::VOCABULARY_UPLOAD_PATH . $new_filename;
                } else {
                    $error = "Ошибка при загрузке изображения.";
                }
            } else {
                $error = "Ошибка при создании директории для загрузки.";
            }
        }
    }
    
    // Обработка загрузки нового аудиофайла
    if (!$error && $_FILES['audio']['size'] > 0) {
        $audio_result = $audioProcessor->processAudioUpload($_FILES['audio']);
        
        if (!$audio_result['success']) {
            $error = implode('<br>', $audio_result['errors']);
        } else {
            // Удаляем старый аудиофайл если он был
            if ($current_audio) {
                $audioProcessor->deleteAudio($current_audio);
            }
            $audio_path = $audio_result['audio_path'];
        }
    }
    
    // Обновляем слово только если нет ошибок
    if (!$error) {
        if ($vocabulary->updateWord($word_id, $foreign_word, $translation, $image_path, $audio_path, $teacher_id)) {
            $success = "Слово успешно обновлено!";
        } else {
            $error = "Ошибка при обновлении слова";
        }
    }
}

// Обработка удаления слова
if ($_GET && isset($_GET['delete_word'])) {
    $vocabulary_id = $_GET['delete_word'];
    if ($vocabulary->deleteWord($vocabulary_id, $teacher_id)) {
        $success = "Слово успешно удалено!";
    } else {
        $error = "Ошибка при удалении слова";
    }
}

$words = $vocabulary->getVocabularyByDeck($deck_id);

// Получаем всех учеников преподавателя и назначенных для этой колоды
$all_students = $user->getStudentsByTeacher($teacher_id);
$assigned_students = $deck->getAssignedStudents($deck_id);
$assigned_student_ids = array_column($assigned_students, 'id');
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="vocabulary_title">QuizCard - Редактирование словаря</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }

        .header {
            background: #667eea;
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 1.5rem;
        }

        .breadcrumb {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            background: rgb(102 126 234);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background: rgb(53 86 233);
        }

        [data-translate-key="back_button"], [data-translate-key="nav_logout"] {
            background: rgba(255, 255, 255, 0.2);
        }

        [data-translate-key="back_button"]:hover, [data-translate-key="nav_logout"]:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .card h2 {
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .student-info {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .student-info h2 {
            border: none;
            color: white;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        input[type="text"], input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .word-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-container {
            position: relative;
            display: inline-block;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            cursor: pointer;
            border-radius: 5px;
        }

        .image-container:hover .image-overlay {
            opacity: 1;
        }

        .edit-icon {
            color: white;
            font-size: 1.2rem;
        }

        .word-edit {
            width: 100%;
            padding: 0.25rem 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            margin: 0.1rem;
        }

        .btn-outline {
            background: transparent;
            border: 1px dashed #667eea;
            color: #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
        }

        /* Модальное окно */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            position: relative;
        }

        .close {
            position: absolute;
            right: 1rem;
            top: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #000;
        }

        .form-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .color-preview {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            margin-left: 10px;
            border: 2px solid #ddd;
            vertical-align: middle;
        }

        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .student-item {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .student-item:hover {
            background: #e9ecef;
        }

        .student-checkbox {
            display: flex;
            align-items: center;
            padding: 1rem;
            cursor: pointer;
            gap: 0.75rem;
            position: relative;
        }

        .student-checkbox input[type="checkbox"] {
            appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #667eea;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            position: relative;
        }

        .student-checkbox input[type="checkbox"]:checked {
            background: #667eea;
        }

        .student-checkbox input[type="checkbox"]:checked::after {
            content: "✓";
            position: absolute;
            color: white;
            font-size: 12px;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .student-info {
            flex: 1;
        }

        .student-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .student-username {
            color: #666;
            font-size: 0.9rem;
        }

        .form-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .students-grid {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .actions {
                flex-direction: column;
            }

            .container {
                padding: 0 1rem;
            }

            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1 data-translate-key="vocabulary_title">📝 Редактирование словаря</h1>
                <div class="breadcrumb">
                    <a href="decks.php" data-translate-key="nav_decks">Колоды</a> → <span data-translate-key="vocabulary_breadcrumb">Словарь колоды</span>
                </div>
            </div>
            <div class="nav-links">
                <a href="decks.php" class="btn" data-translate-key="back_button">← Назад</a>
                <a href="../logout.php" class="btn" data-translate-key="nav_logout">Выйти</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php include 'language_switcher.php'; ?>
        
        <div class="student-info" style="border-left-color: <?php echo htmlspecialchars($current_deck['color']); ?>">
            <h2>Колода: <?php echo htmlspecialchars($current_deck['name']); ?></h2>
            <?php if ($current_deck['description']): ?>
                <p><?php echo htmlspecialchars($current_deck['description']); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($words); ?></div>
                <div class="stat-label" data-translate-key="total_words_stat">Всего слов</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($words, function($w) { return !empty($w['image_path']); })); ?></div>
                <div class="stat-label" data-translate-key="with_images_stat">С изображениями</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo array_sum(array_column($words, 'assigned_students')); ?></div>
                <div class="stat-label" data-translate-key="student_assignments_stat">Назначений ученикам</div>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 data-translate-key="deck_settings">⚙️ Настройки колоды</h2>
            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="deck_name" data-translate-key="deck_name_label">Название колоды:</label>
                        <input type="text" id="deck_name" name="deck_name" value="<?php echo htmlspecialchars($current_deck['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="deck_color" data-translate-key="deck_color_label">Цвет колоды:</label>
                        <div style="display: flex; align-items: center;">
                            <input type="color" id="deck_color" name="deck_color" value="<?php echo htmlspecialchars($current_deck['color']); ?>" 
                                   onchange="updateColorPreview(this.value)">
                            <span class="color-preview" id="colorPreview" style="background-color: <?php echo htmlspecialchars($current_deck['color']); ?>;"></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="daily_word_limit" data-translate-key="daily_word_limit_label">Дневной лимит новых слов:</label>
                        <input type="number" id="daily_word_limit" name="daily_word_limit" min="1" max="100" 
                               value="<?php echo intval($current_deck['daily_word_limit'] ?? 20); ?>" required>
                        <small style="color: #666; font-size: 0.9em;" data-translate-key="daily_word_limit_help">Количество новых слов, которые студент может изучить за день (1-100)</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="deck_description" data-translate-key="deck_description_label">Описание колоды:</label>
                    <textarea id="deck_description" name="deck_description" data-translate-key="deck_description_placeholder" placeholder="Краткое описание темы колоды..."><?php echo htmlspecialchars($current_deck['description'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="edit_deck" class="btn btn-primary" data-translate-key="save_changes_button">💾 Сохранить изменения</button>
            </form>
        </div>

        <div class="card">
            <h2 data-translate-key="add_new_word">Добавить новое слово</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="foreign_word" data-translate-key="foreign_word_label">Изучаемое слово:</label>
                        <input type="text" id="foreign_word" name="foreign_word" required>
                    </div>
                    <div class="form-group">
                        <label for="translation" data-translate-key="translation_label">Перевод:</label>
                        <input type="text" id="translation" name="translation" required>
                    </div>
                    <div class="form-group">
                        <label for="image" data-translate-key="image_label">Изображение (опционально):</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 0.5rem;">
                            <strong data-translate-key="image_constraints_title">Ограничения:</strong><br>
                            • <span data-translate-key="image_max_size_constraint">Максимальный размер: 5MB</span><br>
                            • <span data-translate-key="image_formats_constraint">Форматы: JPG, PNG, GIF, WebP</span>
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="audio" data-translate-key="audio_label">Аудиофайл (опционально):</label>
                        <input type="file" id="audio" name="audio" accept="audio/mp3,audio/wav,audio/ogg,audio/mpeg">
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 0.5rem;">
                            <strong data-translate-key="audio_constraints_title">Ограничения:</strong><br>
                            • <span data-translate-key="audio_max_size_constraint">Максимальный размер: 3MB</span><br>
                            • <span data-translate-key="audio_duration_constraint">Максимальная длительность: 30 секунд</span><br>
                            • <span data-translate-key="audio_formats_constraint">Форматы: MP3, WAV, OGG</span>
                        </small>
                    </div>
                </div>
                <button type="submit" name="add_word" class="btn btn-primary" data-translate-key="add_word_button">Добавить слово</button>
            </form>
        </div>

        <div class="card">
            <h2 data-translate-key="vocabulary_section">Словарь</h2>
            <?php if (empty($words)): ?>
                <p data-translate-key="no_words_yet">В словаре пока нет слов.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th data-translate-key="table_foreign_word">Изучаемое слово</th>
                            <th data-translate-key="table_translation">Перевод</th>
                            <th data-translate-key="table_image">Изображение</th>
                            <th data-translate-key="table_audio">Аудио</th>
                            <th data-translate-key="table_assigned_students">Назначено ученикам</th>
                            <th data-translate-key="table_actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($words as $word): ?>
                            <tr id="word-row-<?php echo $word['id']; ?>">
                                <td>
                                    <span class="word-display" id="foreign-display-<?php echo $word['id']; ?>">
                                        <strong><?php echo htmlspecialchars($word['foreign_word']); ?></strong>
                                    </span>
                                    <input type="text" class="word-edit" id="foreign-edit-<?php echo $word['id']; ?>" 
                                           value="<?php echo htmlspecialchars($word['foreign_word']); ?>" style="display: none;">
                                </td>
                                <td>
                                    <span class="word-display" id="translation-display-<?php echo $word['id']; ?>">
                                        <?php echo htmlspecialchars($word['translation']); ?>
                                    </span>
                                    <input type="text" class="word-edit" id="translation-edit-<?php echo $word['id']; ?>" 
                                           value="<?php echo htmlspecialchars($word['translation']); ?>" style="display: none;">
                                </td>
                                <td>
                                    <div class="image-container" style="position: relative; display: inline-block;">
                                        <?php if ($word['image_path']): ?>
                                            <img src="../<?php echo htmlspecialchars($word['image_path']); ?>" 
                                                 alt="Изображение" class="word-image" 
                                                 onclick="showImageUpload(<?php echo $word['id']; ?>)">
                                            <div class="image-overlay" onclick="showImageUpload(<?php echo $word['id']; ?>)">
                                                <i class="edit-icon">✏️</i>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="showImageUpload(<?php echo $word['id']; ?>)" 
                                                    title="Добавить изображение"
                                                    data-translate-key="add_image_button">
                                                ✏️ Добавить фото
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="audio-container" style="position: relative; display: inline-block;">
                                        <?php if ($word['audio_path']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary audio-play-btn" 
                                                    data-audio-path="../<?php echo htmlspecialchars($word['audio_path']); ?>"
                                                    data-word-id="<?php echo $word['id']; ?>"
                                                    title="Воспроизвести аудио">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    onclick="showAudioUpload(<?php echo $word['id']; ?>)" 
                                                    title="Изменить аудио">
                                                ✏️
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline" 
                                                    onclick="showAudioUpload(<?php echo $word['id']; ?>)" 
                                                    title="Добавить аудиофайл"
                                                    data-translate-key="add_audio_button">
                                                🎵 Добавить аудио
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $word['assigned_students'] ?: 0; ?> <span data-translate-key="students_count">ученика</span></td>
                                <td class="actions">
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            id="edit-btn-<?php echo $word['id']; ?>"
                                            onclick="toggleEdit(<?php echo $word['id']; ?>)"
                                            data-translate-key="edit_word_button">
                                        ✏️ Изменить
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            id="save-btn-<?php echo $word['id']; ?>" 
                                            onclick="saveWord(<?php echo $word['id']; ?>)" 
                                            style="display: none;"
                                            data-translate-key="save_word_button">
                                        💾 Сохранить
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" 
                                            id="cancel-btn-<?php echo $word['id']; ?>" 
                                            onclick="cancelEdit(<?php echo $word['id']; ?>)" 
                                            style="display: none;"
                                            data-translate-key="cancel_edit_button">
                                        ❌ Отмена
                                    </button>
                                    <a href="?deck_id=<?php echo $deck_id; ?>&delete_word=<?php echo $word['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Вы уверены, что хотите удалить это слово?')"
                                       data-confirm-key="delete_word_confirm"
                                       data-translate-key="delete_word_button">
                                        🗑️ Удалить
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Модальное окно для загрузки изображения -->
        <div id="imageModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeImageModal()">&times;</span>
                <h3 data-translate-key="change_image_title">Изменить изображение</h3>
                <form id="imageForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="imageWordId" name="word_id" value="">
                    <input type="hidden" name="current_image" id="currentImagePath" value="">
                    <input type="hidden" name="foreign_word" id="imageFormForeignWord" value="">
                    <input type="hidden" name="translation" id="imageFormTranslation" value="">
                    <input type="hidden" name="edit_word" value="1">
                    
                    <div class="form-group">
                        <label for="newImage" data-translate-key="select_new_image">Выберите новое изображение:</label>
                        <input type="file" id="newImage" name="image" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 0.5rem;">
                            <strong data-translate-key="image_constraints_title">Ограничения:</strong><br>
                            • <span data-translate-key="image_max_size_constraint">Максимальный размер: 5MB</span><br>
                            • <span data-translate-key="image_formats_constraint">Форматы: JPG, PNG, GIF, WebP</span>
                        </small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" data-translate-key="upload_button">📤 Загрузить</button>
                        <button type="button" class="btn btn-secondary" onclick="closeImageModal()" data-translate-key="cancel_button">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Модальное окно для загрузки аудиофайла -->
        <div id="audioModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" onclick="closeAudioModal()">&times;</span>
                <h3 data-translate-key="change_audio_title">Изменить аудиофайл</h3>
                <form id="audioForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="audioWordId" name="word_id" value="">
                    <input type="hidden" name="current_audio" id="currentAudioPath" value="">
                    <input type="hidden" name="current_image" id="audioFormImagePath" value="">
                    <input type="hidden" name="foreign_word" id="audioFormForeignWord" value="">
                    <input type="hidden" name="translation" id="audioFormTranslation" value="">
                    <input type="hidden" name="edit_word" value="1">
                    
                    <div class="form-group audio-upload-container">
                        <label for="newAudio" data-translate-key="select_new_audio">Выберите новый аудиофайл:</label>
                        <input type="file" id="newAudio" name="audio" accept="audio/mp3,audio/wav,audio/ogg,audio/mpeg" required>
                        <small style="color: #666; font-size: 0.85em; display: block; margin-top: 0.5rem;">
                            <strong data-translate-key="audio_constraints_title">Ограничения:</strong><br>
                            • <span data-translate-key="audio_max_size_constraint">Максимальный размер: 3MB</span><br>
                            • <span data-translate-key="audio_duration_constraint">Максимальная длительность: 30 секунд</span><br>
                            • <span data-translate-key="audio_formats_constraint">Форматы: MP3, WAV, OGG</span>
                        </small>
                        <div class="audio-preview-container" style="display: none;"></div>
                        <div class="audio-error-container" style="display: none;"></div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" data-translate-key="upload_button">📤 Загрузить</button>
                        <button type="button" class="btn btn-secondary" onclick="closeAudioModal()" data-translate-key="cancel_button">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Подключение Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Подключение JavaScript для аудио -->
    <script src="../js/audio-player.js"></script>
    <script src="../js/audio-upload.js"></script>

    <script>
        // Переменные для работы со страницей (translations и currentLang уже объявлены в language_switcher.php)
        
        // Данные слов для JavaScript
        const wordsData = <?php echo json_encode(array_column($words, null, 'id')); ?>;
        
        // Функция валидации изображения на клиенте
        function validateImageFile(file) {
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            let errors = [];
            
            if (file.size > maxSize) {
                const sizeErrorMsg = (typeof translations !== 'undefined' && translations[currentLang] && translations[currentLang]['image_validation_client_error'])
                    ? translations[currentLang]['image_validation_client_error'].replace('{max_size}', '5').replace('{current_size}', (file.size / 1024 / 1024).toFixed(2))
                    : `Размер изображения не должен превышать 5MB. Текущий размер: ${(file.size / 1024 / 1024).toFixed(2)}MB`;
                errors.push(sizeErrorMsg);
            }
            
            if (!allowedTypes.includes(file.type)) {
                const formatErrorMsg = (typeof translations !== 'undefined' && translations[currentLang] && translations[currentLang]['image_format_client_error'])
                    ? translations[currentLang]['image_format_client_error'].replace('{allowed_formats}', allowedExtensions.map(ext => ext.toUpperCase()).join(', '))
                    : `Недопустимый формат изображения. Разрешены: ${allowedExtensions.map(ext => ext.toUpperCase()).join(', ')}`;
                errors.push(formatErrorMsg);
            }
            
            return errors;
        }
        
        // Обработчик для валидации при выборе файла в основной форме
        document.addEventListener('DOMContentLoaded', function() {
            // Проверяем доступность переменных из language_switcher.php
            if (typeof translations === 'undefined') {
                console.error('Переводы не загружены');
                return;
            }
            
            // Инициализируем текущий язык если не был установлен
            if (typeof currentLang === 'undefined') {
                currentLang = localStorage.getItem('selectedLanguage') || 'ru';
            }
            
            // Обработчики для валидации файлов
            const imageInput = document.getElementById('image');
            const newImageInput = document.getElementById('newImage');
            
            if (imageInput) {
                imageInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        const errors = validateImageFile(e.target.files[0]);
                        if (errors.length > 0) {
                            alert(errors.join('\n'));
                            e.target.value = '';
                        }
                    }
                });
            }
            
            if (newImageInput) {
                newImageInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        const errors = validateImageFile(e.target.files[0]);
                        if (errors.length > 0) {
                            alert(errors.join('\n'));
                            e.target.value = '';
                        }
                    }
                });
            }
        });
        
        // Функция для обновления превью цвета
        function updateColorPreview(color) {
            const preview = document.getElementById('colorPreview');
            if (preview) {
                preview.style.backgroundColor = color;
            }
        }

        // Переключение режима редактирования слова
        function toggleEdit(wordId) {
            const foreignDisplay = document.getElementById(`foreign-display-${wordId}`);
            const foreignEdit = document.getElementById(`foreign-edit-${wordId}`);
            const translationDisplay = document.getElementById(`translation-display-${wordId}`);
            const translationEdit = document.getElementById(`translation-edit-${wordId}`);
            
            const editBtn = document.getElementById(`edit-btn-${wordId}`);
            const saveBtn = document.getElementById(`save-btn-${wordId}`);
            const cancelBtn = document.getElementById(`cancel-btn-${wordId}`);
            
            // Переключаем отображение
            foreignDisplay.style.display = 'none';
            foreignEdit.style.display = 'block';
            translationDisplay.style.display = 'none';
            translationEdit.style.display = 'block';
            
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
            
            // Фокус на первое поле
            foreignEdit.focus();
        }

        // Отмена редактирования
        function cancelEdit(wordId) {
            const foreignDisplay = document.getElementById(`foreign-display-${wordId}`);
            const foreignEdit = document.getElementById(`foreign-edit-${wordId}`);
            const translationDisplay = document.getElementById(`translation-display-${wordId}`);
            const translationEdit = document.getElementById(`translation-edit-${wordId}`);
            
            const editBtn = document.getElementById(`edit-btn-${wordId}`);
            const saveBtn = document.getElementById(`save-btn-${wordId}`);
            const cancelBtn = document.getElementById(`cancel-btn-${wordId}`);
            
            // Восстанавливаем исходные значения
            if (wordsData[wordId]) {
                foreignEdit.value = wordsData[wordId].foreign_word;
                translationEdit.value = wordsData[wordId].translation;
            }
            
            // Переключаем отображение обратно
            foreignDisplay.style.display = 'block';
            foreignEdit.style.display = 'none';
            translationDisplay.style.display = 'block';
            translationEdit.style.display = 'none';
            
            editBtn.style.display = 'inline-block';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        }

        // Сохранение изменений слова
        function saveWord(wordId) {
            const foreignWord = document.getElementById(`foreign-edit-${wordId}`).value.trim();
            const translation = document.getElementById(`translation-edit-${wordId}`).value.trim();
            
            if (!foreignWord || !translation) {
                const alertMessage = (typeof translations !== 'undefined' && translations[currentLang] && translations[currentLang]['fill_all_fields'])
                    ? translations[currentLang]['fill_all_fields'] 
                    : 'Заполните все поля!';
                alert(alertMessage);
                return;
            }
            
            // Создаем форму для отправки
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            // Добавляем поля
            const fields = {
                'edit_word': '1',
                'word_id': wordId,
                'foreign_word': foreignWord,
                'translation': translation,
                'current_image': wordsData[wordId] ? wordsData[wordId].image_path : ''
            };
            
            for (const [name, value] of Object.entries(fields)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value || '';
                form.appendChild(input);
            }
            
            document.body.appendChild(form);
            form.submit();
        }

        // Показать модальное окно загрузки изображения
        function showImageUpload(wordId) {
            const modal = document.getElementById('imageModal');
            const wordIdInput = document.getElementById('imageWordId');
            const currentImageInput = document.getElementById('currentImagePath');
            const foreignWordInput = document.getElementById('imageFormForeignWord');
            const translationInput = document.getElementById('imageFormTranslation');
            
            if (wordsData[wordId]) {
                wordIdInput.value = wordId;
                currentImageInput.value = wordsData[wordId].image_path || '';
                foreignWordInput.value = wordsData[wordId].foreign_word;
                translationInput.value = wordsData[wordId].translation;
            }
            
            modal.style.display = 'flex';
        }

        // Закрыть модальное окно
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            
            // Очищаем форму
            document.getElementById('imageForm').reset();
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target === modal) {
                closeImageModal();
            }
        }

        // === ФУНКЦИИ ДЛЯ РАБОТЫ С АУДИО ===
        
        // Показать модальное окно для загрузки аудио
        function showAudioUpload(wordId) {
            const word = wordsData[wordId];
            if (!word) return;

            document.getElementById('audioWordId').value = wordId;
            document.getElementById('currentAudioPath').value = word.audio_path || '';
            document.getElementById('audioFormImagePath').value = word.image_path || '';
            document.getElementById('audioFormForeignWord').value = word.foreign_word;
            document.getElementById('audioFormTranslation').value = word.translation;
            
            // Очищаем предыдущий выбор файла
            document.getElementById('newAudio').value = '';
            
            // Очищаем предпросмотр и ошибки
            const previewContainer = document.querySelector('#audioModal .audio-preview-container');
            const errorContainer = document.querySelector('#audioModal .audio-error-container');
            if (previewContainer) {
                previewContainer.innerHTML = '';
                previewContainer.style.display = 'none';
            }
            if (errorContainer) {
                errorContainer.innerHTML = '';
                errorContainer.style.display = 'none';
            }
            
            document.getElementById('audioModal').style.display = 'block';
        }

        // Закрыть модальное окно для аудио
        function closeAudioModal() {
            document.getElementById('audioModal').style.display = 'none';
            
            // Останавливаем предварительное воспроизведение если есть
            if (window.audioUploader && window.audioUploader.previewAudio) {
                window.audioUploader.previewAudio.pause();
            }
        }

        // Обработка клика вне модального окна для аудио
        window.onclick = function(event) {
            const imageModal = document.getElementById('imageModal');
            const audioModal = document.getElementById('audioModal');
            
            if (event.target == imageModal) {
                closeImageModal();
            }
            if (event.target == audioModal) {
                closeAudioModal();
            }
        }

        // Автоматическое скрытие уведомлений через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success, .alert-error');
            alerts.forEach(alert => {
                // Скрываем только если есть видимый контент
                if (alert.offsetHeight > 0 && alert.textContent.trim().length > 0) {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>
