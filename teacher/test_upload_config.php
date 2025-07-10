<?php
/**
 * Тестовый файл для проверки валидации изображений
 * Этот файл можно удалить после тестирования
 */

require_once '../config/upload_config.php';

echo "<h2>Тест конфигурации загрузки изображений</h2>";

echo "<h3>Настройки:</h3>";
echo "<ul>";
echo "<li>Максимальный размер: " . (UploadConfig::VOCABULARY_IMAGE_MAX_SIZE / 1024 / 1024) . " MB</li>";
echo "<li>Разрешенные расширения: " . implode(', ', UploadConfig::VOCABULARY_IMAGE_ALLOWED_EXTENSIONS) . "</li>";
echo "<li>Разрешенные MIME-типы: " . implode(', ', UploadConfig::VOCABULARY_IMAGE_ALLOWED_MIME_TYPES) . "</li>";
echo "<li>Директория загрузки: " . UploadConfig::VOCABULARY_UPLOAD_DIR . "</li>";
echo "<li>Путь для URL: " . UploadConfig::VOCABULARY_UPLOAD_PATH . "</li>";
echo "</ul>";

echo "<h3>Тест функций:</h3>";

// Тест генерации имени файла
$test_filename = "test_image.jpg";
$generated_name = UploadConfig::generateVocabularyImageFilename($test_filename);
echo "<p>Генерация имени файла для '$test_filename': <strong>$generated_name</strong></p>";

// Тест создания директории
$upload_dir = UploadConfig::VOCABULARY_UPLOAD_DIR;
$dir_created = UploadConfig::ensureUploadDirectory($upload_dir);
echo "<p>Создание директории '$upload_dir': " . ($dir_created ? "✅ Успешно" : "❌ Ошибка") . "</p>";

// Имитация валидации файла
$mock_file = [
    'size' => 2 * 1024 * 1024, // 2MB
    'name' => 'test.jpg',
    'tmp_name' => '/tmp/test', // Несуществующий файл для теста
];

$validation_errors = UploadConfig::validateVocabularyImage($mock_file);
echo "<p>Тест валидации файла (без реального файла): ";
if (empty($validation_errors)) {
    echo "✅ Базовые проверки пройдены (ошибки будут только при проверке реального файла)";
} else {
    echo "❌ Ошибки: " . implode(', ', $validation_errors);
}
echo "</p>";

echo "<h3>Информация о PHP:</h3>";
echo "<ul>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>max_file_uploads: " . ini_get('max_file_uploads') . "</li>";
echo "<li>file_uploads: " . (ini_get('file_uploads') ? 'Включен' : 'Отключен') . "</li>";
echo "</ul>";

echo "<p><em>Этот файл можно удалить после проверки.</em></p>";
?>
