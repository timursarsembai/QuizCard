<?php
/**
 * Конфигурация для работы с аудиофайлами
 */

class AudioConfig {
    // Настройки для аудиофайлов
    const AUDIO_MAX_SIZE = 3 * 1024 * 1024; // 3MB в байтах
    const AUDIO_MAX_DURATION = 30; // 30 секунд
    const AUDIO_ALLOWED_EXTENSIONS = ['mp3', 'wav', 'ogg'];
    const AUDIO_ALLOWED_MIME_TYPES = [
        'audio/mpeg',   // MP3
        'audio/mp3',    // MP3 (альтернативный MIME)
        'audio/wav',    // WAV
        'audio/wave',   // WAV (альтернативный MIME)
        'audio/ogg',    // OGG
        'audio/vorbis'  // OGG Vorbis
    ];
    
    // Пути для загрузки аудиофайлов
    const AUDIO_UPLOAD_DIR = '../uploads/audio/';
    const AUDIO_UPLOAD_PATH = 'uploads/audio/';
    const AUDIO_TEMP_DIR = '../uploads/audio/temp/';
    const AUDIO_TEMP_PATH = 'uploads/audio/temp/';
    
    /**
     * Валидация аудиофайла
     * @param array $file Массив $_FILES
     * @return array Массив ошибок (пустой, если нет ошибок)
     */
    public static function validateAudio($file) {
        $errors = [];
        
        // Проверяем, что файл загружен
        if ($file['size'] == 0) {
            return $errors; // Пустой файл - не ошибка, просто пропускаем
        }
        
        // Проверка размера файла
        if ($file['size'] > self::AUDIO_MAX_SIZE) {
            $errors[] = "Размер аудиофайла не должен превышать " . 
                       (self::AUDIO_MAX_SIZE / 1024 / 1024) . "MB. " .
                       "Текущий размер: " . round($file['size'] / 1024 / 1024, 2) . "MB";
        }
        
        // Проверка расширения файла
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, self::AUDIO_ALLOWED_EXTENSIONS)) {
            $errors[] = "Недопустимый формат аудиофайла. Разрешены: " . 
                       implode(', ', array_map('strtoupper', self::AUDIO_ALLOWED_EXTENSIONS));
        }
        
        // Проверка MIME-типа для дополнительной безопасности
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime_type, self::AUDIO_ALLOWED_MIME_TYPES)) {
                $errors[] = "Недопустимый тип файла. Загружайте только аудиофайлы в форматах MP3, WAV или OGG.";
            }
        }
        
        // Проверка длительности аудио (работает для некоторых форматов)
        $duration = self::getAudioDuration($file['tmp_name']);
        if ($duration !== false && $duration > self::AUDIO_MAX_DURATION) {
            $errors[] = "Длительность аудиофайла не должна превышать " . 
                       self::AUDIO_MAX_DURATION . " секунд. " .
                       "Текущая длительность: " . round($duration, 1) . " секунд";
        }
        
        return $errors;
    }
    
    /**
     * Получение длительности аудиофайла
     * @param string $file_path Путь к файлу
     * @return float|false Длительность в секундах или false при ошибке
     */
    public static function getAudioDuration($file_path) {
        // Простое определение длительности для MP3 файлов
        if (function_exists('getid3_lib')) {
            // Если доступна библиотека getid3 (не входит в стандартный PHP)
            // Здесь можно добавить более точное определение длительности
        }
        
        // Базовая проверка размера файла как приблизительного индикатора
        $file_size = filesize($file_path);
        $estimated_duration = $file_size / (128 * 1024 / 8); // Приблизительно для 128kbps MP3
        
        // Если файл слишком большой, скорее всего он длинный
        if ($estimated_duration > self::AUDIO_MAX_DURATION * 2) {
            return $estimated_duration;
        }
        
        return false; // Не можем точно определить
    }
    
    /**
     * Генерация уникального имени файла для аудио
     * @param string $original_filename Оригинальное имя файла
     * @return string Новое имя файла
     */
    public static function generateAudioFilename($original_filename) {
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        return 'audio_' . time() . '_' . uniqid() . '.' . $file_extension;
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
     * Удаление старого аудиофайла
     * @param string $audio_path Путь к аудиофайлу
     * @return bool Результат удаления
     */
    public static function deleteOldAudio($audio_path) {
        if ($audio_path && file_exists('../' . $audio_path)) {
            return @unlink('../' . $audio_path);
        }
        return true;
    }
    
    /**
     * Получение поддерживаемых аудиоформатов для HTML5
     * @return array Массив MIME-типов для HTML5 audio
     */
    public static function getHtml5AudioTypes() {
        return [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg'
        ];
    }
    
    /**
     * Конвертация пути аудиофайла для веб-доступа
     * @param string $audio_path Путь к аудиофайлу
     * @return string Веб-путь к аудиофайлу
     */
    public static function getWebAudioPath($audio_path) {
        if (empty($audio_path)) {
            return '';
        }
        
        // Убираем '../' если есть и добавляем правильный веб-путь
        $clean_path = str_replace('../', '', $audio_path);
        return $clean_path;
    }
}
?>
