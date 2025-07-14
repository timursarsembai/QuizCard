<?php
// Получаем ID колоды из URL или POST
$deck_id = (int)($_GET['deck_id'] ?? $_POST['deck_id'] ?? 0);

// Получаем список колод преподавателя
$decks = [];
$selected_deck = null;
if ($teacher_id > 0) {
    try {
        $decks = $deck->getDecksByTeacher($teacher_id);
        
        // Если указан deck_id в URL, проверяем, что он принадлежит преподавателю
        if ($deck_id > 0) {
            $selected_deck = $deck->getDeckById($deck_id, $teacher_id);
            if (!$selected_deck) {
                $error = translate('deck_not_found_error');
                $deck_id = 0;
            }
        }
    } catch (Exception $e) {
        $error = translate('deck_list_error') . ': ' . $e->getMessage();
    }
}

// Функция обработки импорта файла
function processImportFile($file, $deck_id, $vocabulary, $db) {
    $results = [
        'success' => false,
        'total_rows' => 0,
        'imported' => 0,
        'skipped' => 0,
        'errors' => [],
        'details' => []
    ];
    
    $temp_file = $file['tmp_name'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($file_extension !== 'csv') {
        $results['errors'][] = translate('invalid_file_format');
        return $results;
    }
    
    if (($handle = fopen($temp_file, "r")) !== FALSE) {
        $row_number = 0;
        $headers = null;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row_number++;
            
            // Первая строка - заголовки
            if ($row_number === 1) {
                $headers = array_map('trim', $data);
                continue;
            }
            
            $results['total_rows']++;
            
            // Проверяем минимальное количество колонок
            if (count($data) < 2) {
                $results['skipped']++;
                $results['details'][] = [
                    'row' => $row_number,
                    'status' => 'error',
                    'message' => translate('insufficient_columns')
                ];
                continue;
            }
            
            $term = trim($data[0]);
            $definition = trim($data[1]);
            $audio_file = isset($data[2]) ? trim($data[2]) : '';
            
            // Проверяем обязательные поля
            if (empty($term) || empty($definition)) {
                $results['skipped']++;
                $results['details'][] = [
                    'row' => $row_number,
                    'status' => 'error',
                    'message' => translate('empty_required_fields')
                ];
                continue;
            }
            
            // Проверяем, не существует ли уже такое слово в колоде
            if ($vocabulary->wordExists($term, $deck_id)) {
                $results['skipped']++;
                $results['details'][] = [
                    'row' => $row_number,
                    'status' => 'skipped',
                    'message' => translate('word_already_exists')
                ];
                continue;
            }
            
            // Добавляем слово
            try {
                if ($vocabulary->addWord($deck_id, $term, $definition, $audio_file)) {
                    $results['imported']++;
                    $results['details'][] = [
                        'row' => $row_number,
                        'status' => 'success',
                        'message' => translate('word_imported_successfully')
                    ];
                } else {
                    $results['skipped']++;
                    $results['details'][] = [
                        'row' => $row_number,
                        'status' => 'error',
                        'message' => translate('word_import_failed')
                    ];
                }
            } catch (Exception $e) {
                $results['skipped']++;
                $results['details'][] = [
                    'row' => $row_number,
                    'status' => 'error',
                    'message' => translate('word_import_error') . ': ' . $e->getMessage()
                ];
            }
        }
        
        fclose($handle);
        $results['success'] = true;
    } else {
        $results['errors'][] = translate('file_read_error');
    }
    
    return $results;
}

// Обработка загрузки файла
if ($_POST && isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
    $deck_id = (int)($_POST['deck_id'] ?? $deck_id);
    $file = $_FILES['import_file'];
    
    // Проверяем, что колода принадлежит преподавателю
    $deck_info = $deck->getDeckById($deck_id, $teacher_id);
    
    if (!$deck_info) {
        $error = translate('deck_not_found_error');
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = translate('file_upload_error') . ': ' . $file['error'];
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10 MB
        $error = translate('file_too_large') . '. ' . translate('max_file_size') . ': 10 MB';
    } else {
        // Подсчитываем слова в колоде до импорта
        $words_before = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM vocabulary WHERE deck_id = ?");
            $stmt->execute([$deck_id]);
            $words_before = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Игнорируем ошибку подсчета
        }
        
        $import_results = processImportFile($file, $deck_id, $vocabulary, $db);
        
        // Подсчитываем слова в колоде после импорта
        $words_after = 0;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM vocabulary WHERE deck_id = ?");
            $stmt->execute([$deck_id]);
            $words_after = $stmt->fetchColumn();
        } catch (Exception $e) {
            // Игнорируем ошибку подсчета
        }
        
        if ($import_results['success']) {
            $success = sprintf(
                translate('import_completed'), 
                $import_results['imported'], 
                $import_results['total_rows'], 
                $import_results['skipped']
            );
        } else {
            $error = translate('import_failed') . ': ' . implode(', ', $import_results['errors']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('import_words_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        .import-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .import-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .import-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .import-header p {
            opacity: 0.9;
        }

        .deck-selector {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .deck-selector h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .deck-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }

        .deck-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .deck-option:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }

        .deck-option.selected {
            border-color: #007bff;
            background: rgba(0, 123, 255, 0.1);
        }

        .deck-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-bottom: 0.5rem;
        }

        .deck-name {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .deck-stats {
            font-size: 0.9rem;
            color: #666;
        }

        .upload-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .file-upload {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            background: rgba(0, 123, 255, 0.05);
        }

        .file-upload.dragover {
            border-color: #0056b3;
            background: rgba(0, 123, 255, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .upload-text {
            color: #666;
            margin-bottom: 1rem;
        }

        .file-input {
            display: none;
        }

        .file-button {
            background: #007bff;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .file-button:hover {
            background: #0056b3;
        }

        .file-info {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
        }

        .format-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .format-title {
            font-weight: 600;
            color: #856404;
            margin-bottom: 0.5rem;
        }

        .format-example {
            font-family: monospace;
            background: white;
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-top: 0.5rem;
        }

        .results-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .results-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .result-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .result-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .result-label {
            font-size: 0.9rem;
            color: #666;
        }

        .result-details {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .detail-item {
            padding: 0.8rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-skipped { color: #ffc107; }

        @media (max-width: 768px) {
            .deck-grid {
                grid-template-columns: 1fr;
            }
            
            .results-summary {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="container">
        <div class="import-container">
            <div class="import-header">
                <h1 data-translate-key="import_words_title"><?php echo translate('import_words_title'); ?></h1>
                <p data-translate-key="import_words_subtitle"><?php echo translate('import_words_subtitle'); ?></p>
            </div>

            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success) && !empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($decks)): ?>
                <div class="card">
                    <div class="empty-state">
                        <h3 data-translate-key="no_decks_title"><?php echo translate('no_decks_title'); ?></h3>
                        <p data-translate-key="no_decks_desc"><?php echo translate('no_decks_desc'); ?></p>
                        <a href="/teacher/decks" class="btn btn-primary" data-translate-key="create_deck">
                            <?php echo translate('create_deck'); ?>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data" id="importForm">
                    <div class="deck-selector">
                        <h3 data-translate-key="select_deck"><?php echo translate('select_deck'); ?></h3>
                        <div class="deck-grid">
                            <?php foreach ($decks as $deck_item): ?>
                                <div class="deck-option <?php echo $deck_id == $deck_item['id'] ? 'selected' : ''; ?>"
                                     onclick="selectDeck(<?php echo $deck_item['id']; ?>)">
                                    <div class="deck-color" style="background-color: <?php echo htmlspecialchars($deck_item['color']); ?>"></div>
                                    <div class="deck-name"><?php echo htmlspecialchars($deck_item['name']); ?></div>
                                    <div class="deck-stats">
                                        <span data-translate-key="words_count"><?php echo translate('words_count'); ?></span>: <?php echo $deck_item['word_count']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="deck_id" id="selected_deck_id" value="<?php echo $deck_id; ?>">
                    </div>

                    <div class="upload-section">
                        <div class="format-info">
                            <div class="format-title" data-translate-key="csv_format_info"><?php echo translate('csv_format_info'); ?></div>
                            <p data-translate-key="csv_format_desc"><?php echo translate('csv_format_desc'); ?></p>
                            <div class="format-example">
                                term,definition,audio_file<br>
                                apple,яблоко,apple.mp3<br>
                                book,книга,<br>
                                house,дом,house.wav
                            </div>
                            <a href="/teacher/sample_import.csv" class="btn btn-secondary" style="margin-top: 0.5rem;" data-translate-key="download_sample">
                                <?php echo translate('download_sample'); ?>
                            </a>
                        </div>

                        <div class="file-upload" id="fileUpload">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text" data-translate-key="drag_drop_text"><?php echo translate('drag_drop_text'); ?></div>
                            <button type="button" class="file-button" onclick="document.getElementById('fileInput').click()">
                                <span data-translate-key="choose_file"><?php echo translate('choose_file'); ?></span>
                            </button>
                            <input type="file" 
                                   id="fileInput" 
                                   name="import_file" 
                                   accept=".csv" 
                                   class="file-input" 
                                   onchange="handleFileSelect(this)">
                        </div>

                        <div class="file-info" id="fileInfo">
                            <strong data-translate-key="selected_file"><?php echo translate('selected_file'); ?>:</strong> 
                            <span id="fileName"></span><br>
                            <strong data-translate-key="file_size"><?php echo translate('file_size'); ?>:</strong> 
                            <span id="fileSize"></span>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;" id="submitBtn" disabled>
                            <span data-translate-key="import_words"><?php echo translate('import_words'); ?></span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($import_results)): ?>
                <div class="results-section">
                    <h3 data-translate-key="import_results"><?php echo translate('import_results'); ?></h3>
                    
                    <div class="results-summary">
                        <div class="result-stat">
                            <div class="result-number" style="color: #007bff;"><?php echo $import_results['total_rows']; ?></div>
                            <div class="result-label" data-translate-key="total_rows"><?php echo translate('total_rows'); ?></div>
                        </div>
                        <div class="result-stat">
                            <div class="result-number" style="color: #28a745;"><?php echo $import_results['imported']; ?></div>
                            <div class="result-label" data-translate-key="imported"><?php echo translate('imported'); ?></div>
                        </div>
                        <div class="result-stat">
                            <div class="result-number" style="color: #ffc107;"><?php echo $import_results['skipped']; ?></div>
                            <div class="result-label" data-translate-key="skipped"><?php echo translate('skipped'); ?></div>
                        </div>
                        <div class="result-stat">
                            <div class="result-number" style="color: #dc3545;"><?php echo count($import_results['errors']); ?></div>
                            <div class="result-label" data-translate-key="errors"><?php echo translate('errors'); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($import_results['details'])): ?>
                        <div class="result-details">
                            <?php foreach ($import_results['details'] as $detail): ?>
                                <div class="detail-item">
                                    <span>
                                        <span data-translate-key="row"><?php echo translate('row'); ?></span> <?php echo $detail['row']; ?>
                                    </span>
                                    <span class="status-<?php echo $detail['status']; ?>">
                                        <?php echo htmlspecialchars($detail['message']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
    
    <script src="/public/js/security.js"></script>
    <script>
        function selectDeck(deckId) {
            // Убираем выделение со всех колод
            document.querySelectorAll('.deck-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Добавляем выделение к выбранной колоде
            event.target.closest('.deck-option').classList.add('selected');
            
            // Устанавливаем значение в скрытое поле
            document.getElementById('selected_deck_id').value = deckId;
            
            // Проверяем возможность отправки формы
            updateSubmitButton();
        }

        function handleFileSelect(input) {
            const file = input.files[0];
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
            
            updateSubmitButton();
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function updateSubmitButton() {
            const deckSelected = document.getElementById('selected_deck_id').value;
            const fileSelected = document.getElementById('fileInput').files.length > 0;
            const submitBtn = document.getElementById('submitBtn');
            
            submitBtn.disabled = !(deckSelected && fileSelected);
        }

        // Drag and drop functionality
        const fileUpload = document.getElementById('fileUpload');
        const fileInput = document.getElementById('fileInput');

        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });

        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
        });

        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        });

        // Инициализация
        updateSubmitButton();
    </script>
</body>
</html>
