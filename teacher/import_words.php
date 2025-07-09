<?php
// Включаем отображение всех ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Vocabulary.php';
require_once '../classes/Deck.php';
require_once '../includes/translations.php';

$database = new Database();
$db = $database->getConnection();

// Проверяем подключение к БД
if (!$db) {
    $lang = $_SESSION['language'] ?? 'ru';
    $t = $translations[$lang] ?? $translations['ru'];
    $error = $t['error_db_connection_import'] . ' ' . $database->getError();
}

$user = new User($db);
$vocabulary = new Vocabulary($db);
$deck = new Deck($db);

// Проверяем авторизацию, только если есть подключение к БД
if ($db && (!$user->isLoggedIn() || $user->getRole() !== 'teacher')) {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'] ?? 0;
$error = '';
$success = '';
$import_results = [];

// Получаем ID колоды из URL или POST
$deck_id = (int)($_GET['deck_id'] ?? $_POST['deck_id'] ?? 0);

// Получаем список колод преподавателя, только если есть подключение к БД
$decks = [];
$selected_deck = null;
if ($db && $teacher_id > 0) {
    try {
        $decks = $deck->getDecksByTeacher($teacher_id);
        
        // Если указан deck_id в URL, проверяем, что он принадлежит преподавателю
        if ($deck_id > 0) {
            $selected_deck = $deck->getDeckById($deck_id, $teacher_id);
            if (!$selected_deck) {
                $error = 'Указанная колода не найдена или не принадлежит вам';
                $deck_id = 0;
            }
        }
    } catch (Exception $e) {
        $error = 'Ошибка при получении списка колод: ' . $e->getMessage();
    }
}

// Обработка загрузки файла
if ($_POST && isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK && $db) {
    $deck_id = (int)($_POST['deck_id'] ?? $deck_id); // Используем deck_id из формы или URL
    $file = $_FILES['import_file'];
    
    // Проверяем, что колода принадлежит преподавателю
    $deck_info = $deck->getDeckById($deck_id, $teacher_id);
    
    if (!$deck_info) {
        $lang = $_SESSION['language'] ?? 'ru';
        $t = $translations[$lang] ?? $translations['ru'];
        $error = $t['error_deck_not_found'] ?? 'Выбранная колода не найдена или не принадлежит вам';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $lang = $_SESSION['language'] ?? 'ru';
        $t = $translations[$lang] ?? $translations['ru'];
        $error = ($t['error_file_upload'] ?? 'Ошибка при загрузке файла') . ': ' . $file['error'];
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10 MB
        $lang = $_SESSION['language'] ?? 'ru';
        $t = $translations[$lang] ?? $translations['ru'];
        $error = ($t['file_too_large'] ?? 'Файл слишком большой') . '. ' . ($t['max_file_size'] ?? 'Максимальный размер') . ': 10 MB';
    } else {
        
        // Подсчитываем слова в колоде до импорта
        $words_before = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM vocabulary WHERE deck_id = ?");
            $stmt->execute([$deck_id]);
            $words_before = $stmt->fetchColumn();
        } catch (Exception $e) {
        }
        
        $import_results = processImportFile($file, $deck_id, $vocabulary, $db);
        
        // Подсчитываем слова в колоде после импорта
        $words_after = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM vocabulary WHERE deck_id = ?");
            $stmt->execute([$deck_id]);
            $words_after = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Тихо игнорируем ошибки подсчета
        }
        
        $actually_added = $words_after - $words_before;
        
        if (isset($import_results['error'])) {
            $error = $import_results['error'];
        } else {
            // Получаем переводы для текущего языка
            $lang = $_SESSION['language'] ?? 'ru';
            $t = $translations[$lang] ?? $translations['ru'];
            
            $success = $t['import_completed'] . " " . count($import_results['details']) . 
                      ", " . $t['actually_added_db'] . " {$actually_added} " . $t['words_word'] .
                      ", " . $t['skipped_word'] . " {$import_results['skipped']}, " . $t['errors_word'] . " {$import_results['errors']}";
            
            // Добавляем информацию о фактическом результате
            $import_results['actually_added'] = $actually_added;
            $import_results['words_before'] = $words_before;
            $import_results['words_after'] = $words_after;
            
            // Логируем активность импорта
            logImportActivity($teacher_id, $deck_id, $file['name'], $import_results);
        }
    }
} elseif ($_POST && isset($_POST['import_file']) && !$db) {
    $lang = $_SESSION['language'] ?? 'ru';
    $t = $translations[$lang] ?? $translations['ru'];
    $error = $t['error_db_connection_import'] ?? 'Невозможно выполнить импорт: нет подключения к базе данных';
}

function processImportFile($file, $deck_id, $vocabulary, $db) {
    $results = ['added' => 0, 'skipped' => 0, 'errors' => 0, 'details' => []];
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $temp_file = $file['tmp_name'];
    
    try {
        if ($file_extension === 'csv') {
            $data = parseCSV($temp_file);
        } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
            $data = parseExcel($temp_file, $file_extension);
        } else {
            return ['error' => 'Неподдерживаемый формат файла. Используйте CSV, XLS или XLSX'];
        }
        
        
        foreach ($data as $row_num => $row) {
            $result = processRow($row, $deck_id, $vocabulary, $row_num + 1);
            
            $results['added'] += $result['added'];
            $results['skipped'] += $result['skipped'];
            $results['errors'] += $result['errors'];
            if (!empty($result['message'])) {
                $results['details'][] = "Строка " . ($row_num + 1) . ": {$result['message']}";
            }
        }
        
        
    } catch (Exception $e) {
        return ['error' => 'Ошибка обработки файла: ' . $e->getMessage()];
    }
    
    return $results;
}

function parseCSV($file_path) {
    $data = [];
    $header = null;
    
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if ($header === null) {
                $header = array_map('strtolower', array_map('trim', $row));
            } else {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }
    
    return $data;
}

function parseExcel($file_path, $extension) {
    // Простое решение для классического хостинга - конвертируем Excel в CSV
    if ($extension === 'xlsx') {
        return parseExcelSimple($file_path);
    } elseif ($extension === 'xls') {
        return parseOldExcel($file_path);
    } else {
        throw new Exception('Неподдерживаемый формат Excel файла');
    }
}

function parseExcelSimple($file_path) {
    // Простой парсер для .xlsx файлов (без PhpSpreadsheet)
    // .xlsx файлы - это ZIP архивы с XML файлами
    
    if (!class_exists('ZipArchive')) {
        throw new Exception('Для работы с Excel файлами требуется расширение ZIP в PHP. Используйте CSV формат.');
    }
    
    $zip = new ZipArchive();
    if ($zip->open($file_path) !== TRUE) {
        throw new Exception('Не удалось открыть Excel файл. Возможно, файл поврежден.');
    }
    
    // Читаем основные данные листа
    $sheet_data = $zip->getFromName('xl/worksheets/sheet1.xml');
    $shared_strings = $zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    
    if ($sheet_data === false) {
        throw new Exception('Не удалось прочитать данные из Excel файла');
    }
    
    // Парсим shared strings (общие строки)
    $strings = [];
    if ($shared_strings !== false) {
        $strings = parseSharedStrings($shared_strings);
    }
    
    // Парсим данные листа
    return parseSheetData($sheet_data, $strings);
}

function parseSharedStrings($xml_content) {
    $strings = [];
    
    // Простой парсинг XML с помощью регулярных выражений
    if (preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $xml_content, $matches)) {
        foreach ($matches[1] as $match) {
            $strings[] = html_entity_decode($match, ENT_XML1, 'UTF-8');
        }
    }
    
    return $strings;
}

function parseSheetData($xml_content, $strings) {
    $data = [];
    $header = null;
    
    // Парсим строки
    if (preg_match_all('/<row[^>]*r="(\d+)"[^>]*>(.*?)<\/row>/s', $xml_content, $row_matches)) {
        foreach ($row_matches[0] as $i => $row_xml) {
            $row_num = intval($row_matches[1][$i]);
            $row_data = [];
            
            // Парсим ячейки в строке
            if (preg_match_all('/<c[^>]*r="([A-Z]+)\d+"[^>]*(?:\st="([^"]*)")?[^>]*>(.*?)<\/c>/s', $row_xml, $cell_matches)) {
                $cell_values = [];
                
                foreach ($cell_matches[0] as $j => $cell_xml) {
                    $col = $cell_matches[1][$j];
                    $type = $cell_matches[2][$j] ?? '';
                    $cell_content = $cell_matches[3][$j];
                    
                    $value = '';
                    if (preg_match('/<v[^>]*>(.*?)<\/v>/', $cell_content, $v_match)) {
                        $value = $v_match[1];
                        
                        // Если это ссылка на shared string
                        if ($type === 's' && isset($strings[intval($value)])) {
                            $value = $strings[intval($value)];
                        }
                    }
                    
                    $cell_values[$col] = $value;
                }
                
                // Сортируем по колонкам (A, B, C, ...)
                ksort($cell_values);
                $row_data = array_values($cell_values);
            }
            
            if ($row_num === 1) {
                $header = array_map('strtolower', array_map('trim', $row_data));
            } else {
                if (!empty($row_data) && count($header) > 0) {
                    // Дополняем строку пустыми значениями если нужно
                    while (count($row_data) < count($header)) {
                        $row_data[] = '';
                    }
                    
                    if (count($row_data) >= count($header)) {
                        $data[] = array_combine($header, array_slice($row_data, 0, count($header)));
                    }
                }
            }
        }
    }
    
    return $data;
}

function parseOldExcel($file_path) {
    // Для старых .xls файлов предлагаем использовать CSV
    throw new Exception('Старые Excel файлы (.xls) не поддерживаются на классическом хостинге. Сохраните файл как .xlsx или .csv');
}

function processRow($row, $deck_id, $vocabulary, $row_num) {
    $result = ['added' => 0, 'skipped' => 0, 'errors' => 0, 'message' => ''];
    
    // Ожидаемые колонки: foreign_word, translation, image (опционально)
    $foreign_word = trim($row['foreign_word'] ?? $row['word'] ?? '');
    $translation = trim($row['translation'] ?? $row['translate'] ?? '');
    $image_data = trim($row['image'] ?? $row['images'] ?? '');
    
    if (empty($foreign_word) || empty($translation)) {
        $result['skipped']++;
        $result['message'] = 'Пропущено (отсутствует слово или перевод)';
        return $result;
    }
    
    try {
        // Обработка изображения
        $image_path = null;
        if (!empty($image_data)) {
            $image_path = downloadAndSaveImage($image_data, $foreign_word);
        }
        
        // Добавляем слово в колоду
        if ($vocabulary->addWordSafe($deck_id, $foreign_word, $translation, $image_path)) {
            $result['added']++;
            $result['message'] = 'Добавлено успешно';
        } else {
            $result['skipped']++;
            $result['message'] = 'Пропущено (возможно, уже существует)';
        }
        
    } catch (Exception $e) {
        $result['errors']++;
        $result['message'] = 'Ошибка: ' . $e->getMessage();
    }
    
    return $result;
}

function downloadAndSaveImage($image_data, $word_hint) {
    // Создаем директорию для изображений если её нет
    $upload_dir = '../uploads/vocabulary/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Проверяем, является ли это URL
    if (filter_var($image_data, FILTER_VALIDATE_URL)) {
        return downloadImageFromUrl($image_data, $upload_dir, $word_hint);
    }
    
    // Если это base64 данные (из Excel)
    if (strpos($image_data, 'data:image/') === 0) {
        return saveBase64Image($image_data, $upload_dir, $word_hint);
    }
    
    throw new Exception('Неподдерживаемый формат изображения');
}

function downloadImageFromUrl($url, $upload_dir, $word_hint) {
    // Получаем расширение из URL
    $path_info = pathinfo(parse_url($url, PHP_URL_PATH));
    $extension = $path_info['extension'] ?? 'jpg';
    
    // Проверяем допустимые расширения
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($extension), $allowed_extensions)) {
        $extension = 'jpg';
    }
    
    // Генерируем уникальное имя файла
    $filename = 'import_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $word_hint) . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // Скачиваем изображение
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (compatible; QuizCard/1.0)'
        ]
    ]);
    
    $image_data = @file_get_contents($url, false, $context);
    if ($image_data === false) {
        throw new Exception('Не удалось скачать изображение с URL: ' . $url);
    }
    
    // Проверяем, что это действительно изображение
    $image_info = @getimagesizefromstring($image_data);
    if ($image_info === false) {
        throw new Exception('Скачанный файл не является изображением: ' . $url);
    }
    
    // Сохраняем файл
    if (file_put_contents($file_path, $image_data) === false) {
        throw new Exception('Не удалось сохранить изображение');
    }
    
    return 'uploads/vocabulary/' . $filename;
}

function saveBase64Image($base64_data, $upload_dir, $word_hint) {
    // Извлекаем тип изображения и данные
    if (preg_match('/^data:image\/([a-zA-Z]+);base64,(.+)$/', $base64_data, $matches)) {
        $image_type = $matches[1];
        $image_data = base64_decode($matches[2]);
        
        if ($image_data === false) {
            throw new Exception('Некорректные base64 данные изображения');
        }
        
        // Генерируем имя файла
        $filename = 'import_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $word_hint) . '.' . $image_type;
        $file_path = $upload_dir . $filename;
        
        // Сохраняем файл
        if (file_put_contents($file_path, $image_data) === false) {
            throw new Exception('Не удалось сохранить изображение');
        }
        
        return 'uploads/vocabulary/' . $filename;
    }
    
    throw new Exception('Некорректный формат base64 изображения');
}

function logImportActivity($teacher_id, $deck_id, $file_name, $results) {
    // Создаем директорию для логов если её нет
    $log_dir = '../logs/';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . 'import_' . date('Y-m') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_entry = [
        'timestamp' => $timestamp,
        'teacher_id' => $teacher_id,
        'deck_id' => $deck_id,
        'file_name' => $file_name,
        'results' => $results
    ];
    
    $log_line = $timestamp . " - Teacher {$teacher_id} - Deck {$deck_id} - File: {$file_name} - " .
                "Added: {$results['added']}, Skipped: {$results['skipped']}, Errors: {$results['errors']}\n";
    
    file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-translate-key="import_words_title">Импорт слов из файла</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .content {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        select, input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        select:focus, input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-right: 1rem;
        }

        .error {
            background: #ffe6e6;
            color: #d00;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #d00;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }

        .info-box {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            border-left: 4px solid #17a2b8;
        }

        .info-box h3 {
            margin-bottom: 1rem;
            color: #0c5460;
        }

        .info-box ul {
            margin-left: 1rem;
            line-height: 1.6;
        }

        .format-example {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }

        .actions > div {
            display: flex;
            gap: 0.5rem;
        }

        .import-results {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .import-results h4 {
            color: #333;
            margin-bottom: 1rem;
        }

        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Language Switcher */
        .language-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .language-switcher button {
            background: none;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #667eea;
        }
        
        .language-switcher button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .language-switcher button:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .actions {
                flex-direction: column;
                gap: 1rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'language_switcher.php'; ?>
        
        <div class="header">
            <h1 data-translate-key="import_words_title">📤 Импорт слов из файла</h1>
            <?php if ($selected_deck): ?>
                <p data-translate-key="import_words_deck_description">Массовая загрузка в колоду "<?php echo htmlspecialchars($selected_deck['name']); ?>"</p>
            <?php else: ?>
                <p data-translate-key="import_words_description">Массовая загрузка словаря из Excel или CSV файлов</p>
            <?php endif; ?>
        </div>

        <div class="content">
            <?php if ($error): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                    <?php if (strpos($error, 'подключения к базе данных') !== false): ?>
                        <br><br>
                        <strong>💡 <span data-translate-key="error_db_connection_tip">Совет:</span></strong> <span data-translate-key="error_db_connection_tip">Проверьте настройки в config/database.php и убедитесь, что база данных создана.</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])): ?>
                <div class="info-box" style="background: #fff3cd; color: #856404; border-left: 4px solid #ffc107;">
                    <h4 data-translate-key="debug_info">🔍 Отладочная информация</h4>
                    <strong data-translate-key="request_method">Метод запроса:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
                    <strong data-translate-key="post_data">POST данные:</strong> <?php echo !empty($_POST) ? '<span data-translate-key="present">Присутствуют</span>' : '<span data-translate-key="absent">Отсутствуют</span>'; ?><br>
                    <strong data-translate-key="files_data">FILES данные:</strong> <?php echo isset($_FILES['import_file']) ? '<span data-translate-key="present">Присутствуют</span>' : '<span data-translate-key="absent">Отсутствуют</span>'; ?><br>
                    <strong data-translate-key="db_connection">Подключение к БД:</strong> <?php echo $db ? '<span data-translate-key="yes">Да</span>' : '<span data-translate-key="no">Нет</span>'; ?><br>
                    <?php if (isset($_FILES['import_file'])): ?>
                        <strong data-translate-key="file_name">Файл:</strong> <?php echo htmlspecialchars($_FILES['import_file']['name']); ?><br>
                        <strong data-translate-key="file_size">Размер:</strong> <?php echo $_FILES['import_file']['size']; ?> <span data-translate-key="bytes">байт</span><br>
                        <strong data-translate-key="upload_error">Ошибка загрузки:</strong> <?php echo $_FILES['import_file']['error']; ?><br>
                        <strong data-translate-key="temp_file_exists">Временный файл существует:</strong> <?php echo file_exists($_FILES['import_file']['tmp_name']) ? '<span data-translate-key="yes">Да</span>' : '<span data-translate-key="no">Нет</span>'; ?><br>
                    <?php endif; ?>
                    <strong>Deck ID:</strong> <?php echo $deck_id; ?><br>
                    <strong>Teacher ID:</strong> <?php echo $teacher_id; ?><br>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
                
                <?php if (!empty($import_results['details'])): ?>
                    <div class="import-results">
                        <h4 data-translate-key="import_details">Детали импорта:</h4>
                        <div class="result-stats">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $import_results['actually_added'] ?? $import_results['added']; ?></div>
                                <div class="stat-label" data-translate-key="actually_added_db_stat">Фактически добавлено в БД</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $import_results['skipped']; ?></div>
                                <div class="stat-label" data-translate-key="skipped_stat">Пропущено</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $import_results['errors']; ?></div>
                                <div class="stat-label" data-translate-key="errors_stat">Ошибок</div>
                            </div>
                        </div>
                        
                        <?php if (isset($import_results['words_before'], $import_results['words_after'])): ?>
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                                <strong data-translate-key="db_statistics">📊 Статистика БД:</strong><br>
                                <span data-translate-key="words_before">Слов в колоде было:</span> <?php echo $import_results['words_before']; ?><br>
                                <span data-translate-key="words_after">Слов в колоде стало:</span> <?php echo $import_results['words_after']; ?><br>
                                <span data-translate-key="growth">Прирост:</span> +<?php echo $import_results['actually_added']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <details>
                            <summary data-translate-key="detailed_report">Подробный отчет</summary>
                            <ul style="margin-top: 1rem;">
                                <?php foreach ($import_results['details'] as $detail): ?>
                                    <li><?php echo htmlspecialchars($detail); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <?php if ($selected_deck): ?>
                    <!-- Колода уже выбрана из URL -->
                    <input type="hidden" name="deck_id" value="<?php echo $deck_id; ?>">
                    <div class="form-group">
                        <label data-translate-key="import_to_deck">Импорт в колоду:</label>
                        <div style="padding: 0.75rem; background: #e9ecef; border-radius: 8px; font-weight: 500;">
                            📚 <?php echo htmlspecialchars($selected_deck['name']); ?>
                            <?php if ($selected_deck['description']): ?>
                                <br><small style="color: #6c757d;"><?php echo htmlspecialchars($selected_deck['description']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Выбор колоды вручную -->
                    <div class="form-group">
                        <label for="deck_id" data-translate-key="select_deck">Выберите колоду:</label>
                        <select id="deck_id" name="deck_id" required>
                            <option value="" data-translate-key="select_deck_option">-- Выберите колоду --</option>
                            <?php foreach ($decks as $deck_item): ?>
                                <option value="<?php echo $deck_item['id']; ?>">
                                    <?php echo htmlspecialchars($deck_item['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="import_file" data-translate-key="select_file_label">Выберите файл для импорта:</label>
                    <input type="file" id="import_file" name="import_file" 
                           accept=".csv,.xls,.xlsx" required>
                </div>

                <button type="submit" name="import_file" class="btn btn-primary" data-translate-key="import_words_button">
                    📤 Импортировать слова
                </button>
            </form>

            <div class="info-box">
                <h3 data-translate-key="import_format_title">📋 Формат файла</h3>
                <p data-translate-key="import_format_description">Файл должен содержать следующие колонки:</p>
                <ul>
                    <li><strong data-translate-key="foreign_word_column">foreign_word</strong> (или word) - иностранное слово</li>
                    <li><strong data-translate-key="translation_column">translation</strong> (или translate) - перевод</li>
                    <li><strong data-translate-key="image_column">image</strong> (или images) - URL изображения (опционально)</li>
                </ul>
                
                <h4 data-translate-key="csv_example_title">Пример CSV файла:</h4>
                <div class="format-example">
foreign_word,translation,image<br>
apple,яблоко,https://example.com/apple.jpg<br>
house,дом,https://example.com/house.png<br>
car,машина,
                </div>

                <h4 data-translate-key="image_support_title">Поддержка изображений:</h4>
                <ul>
                    <li data-translate-key="image_support_urls">URL ссылки на изображения (автоматическая загрузка)</li>
                    <li data-translate-key="image_supported_formats">Поддерживаемые форматы: JPG, PNG, GIF, WebP</li>
                </ul>
            </div>

            <div class="actions">
                <?php if ($selected_deck): ?>
                    <a href="vocabulary.php?deck_id=<?php echo $deck_id; ?>" class="btn btn-secondary" data-translate-key="back_to_deck">← Назад к колоде "<?php echo htmlspecialchars($selected_deck['name']); ?>"</a>
                <?php else: ?>
                    <a href="decks.php" class="btn btn-secondary" data-translate-key="back_to_decks">← Назад к колодам</a>
                <?php endif; ?>
                <div>
                    <a href="sample_import.csv" class="btn btn-secondary" download data-translate-key="download_csv_sample">📥 Скачать пример CSV</a>
                    <a href="sample_import.xlsx" class="btn btn-secondary" download data-translate-key="download_excel_sample">📥 Скачать Excel (.xlsx)</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Переводы для JavaScript
        const translations = <?php echo json_encode($translations); ?>;
        let currentLang = localStorage.getItem('selectedLanguage') || 'ru';
        
        // Индикатор прогресса для загрузки файлов
        document.getElementById('import_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2); // MB
                const maxSize = 10; // 10 MB
                
                if (file.size > maxSize * 1024 * 1024) {
                    const fileTooLarge = translations[currentLang]['file_too_large'] || 'Файл слишком большой';
                    const maxFileSize = translations[currentLang]['max_file_size'] || 'Максимальный размер:';
                    alert(`${fileTooLarge} (${fileSize} MB). ${maxFileSize} ${maxSize} MB`);
                    e.target.value = '';
                    return;
                }
            }
        });
        
        // Показ индикатора при отправке формы
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('button[type="submit"]');
            const file = document.getElementById('import_file').files[0];
            
            if (file) {
                const importingProgress = translations[currentLang]['importing_progress'] || 'Импортируем...';
                submitBtn.innerHTML = `⏳ ${importingProgress}`;
                submitBtn.disabled = true;
                
                // Создаем индикатор прогресса
                const progressContainer = document.createElement('div');
                const processingFile = translations[currentLang]['processing_file'] || 'Обрабатываем файл:';
                const pleaseWait = translations[currentLang]['please_wait'] || 'Пожалуйста, подождите...';
                progressContainer.innerHTML = `
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <div>📤 ${processingFile} ${file.name}</div>
                        <div style="margin-top: 0.5rem; color: #666;">${pleaseWait}</div>
                    </div>
                `;
                submitBtn.parentNode.appendChild(progressContainer);
            }
        });
        
        // Автоматическое скрытие сообщений
        setTimeout(function() {
            const alerts = document.querySelectorAll('.error, .success');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 8000);
    </script>
</body>
</html>
