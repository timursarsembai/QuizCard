<?php
/**
 * Конфигурация для загрузки файлов
 */

class UploadConfig {
    // Настройки для изображений словаря
    const VOCABULARY_IMAGE_MAX_SIZE = 5 * 1024 * 1024; // 5MB в байтах
    const VOCABULARY_IMAGE_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const VOCABULARY_IMAGE_ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    // Путь для загрузки изображений словаря
    const VOCABULARY_UPLOAD_DIR = '../uploads/vocabulary/';
    const VOCABULARY_UPLOAD_PATH = 'uploads/vocabulary/';
    
    /**
     * Валидация изображения
     * @param array $file Массив $_FILES
     * @return array Массив ошибок (пустой, если нет ошибок)
     */
    public static function validateVocabularyImage($file) {
        $errors = [];
        
        // Проверяем, что файл загружен
        if ($file['size'] == 0) {
            return $errors; // Пустой файл - не ошибка, просто пропускаем
        }
        
        // Проверка размера файла
        if ($file['size'] > self::VOCABULARY_IMAGE_MAX_SIZE) {
            $errors[] = "Размер изображения не должен превышать " . 
                       (self::VOCABULARY_IMAGE_MAX_SIZE / 1024 / 1024) . "MB. " .
                       "Текущий размер: " . round($file['size'] / 1024 / 1024, 2) . "MB";
        }
        
        // Проверка расширения файла
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, self::VOCABULARY_IMAGE_ALLOWED_EXTENSIONS)) {
            $errors[] = "Недопустимый формат изображения. Разрешены: " . 
                       implode(', ', array_map('strtoupper', self::VOCABULARY_IMAGE_ALLOWED_EXTENSIONS));
        }
        
        // Проверка MIME-типа для дополнительной безопасности
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, self::VOCABULARY_IMAGE_ALLOWED_MIME_TYPES)) {
                $errors[] = "Недопустимый тип файла. Загружайте только изображения.";
            }
        }
        
        // Дополнительная проверка через getimagesize
        $image_info = @getimagesize($file['tmp_name']);
        if ($image_info === false) {
            $errors[] = "Загруженный файл не является корректным изображением.";
        }
        
        return $errors;
    }
    
    /**
     * Генерация уникального имени файла для изображения словаря
     * @param string $original_filename Оригинальное имя файла
     * @return string Новое имя файла
     */
    public static function generateVocabularyImageFilename($original_filename) {
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        return 'vocab_' . time() . '_' . uniqid() . '.' . $file_extension;
    }
    
    /**
     * Создание директории для загрузки, если она не существует
     * @param string $upload_dir Путь к директории
     * @return bool Результат создания
     */
    public static function ensureUploadDirectory($upload_dir) {
        if (!file_exists($upload_dir)) {
            return mkdir($upload_dir, 0755, true);
        }
        return true;
    }
    
    /**
     * Удаление старого изображения
     * @param string $image_path Путь к изображению
     * @return bool Результат удаления
     */
    public static function deleteOldImage($image_path) {
        if ($image_path && file_exists('../' . $image_path)) {
            return @unlink('../' . $image_path);
        }
        return true;
    }
}
?>
