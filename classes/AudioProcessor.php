<?php
/**
 * Класс для обработки аудиофайлов
 */

require_once '../config/audio_config.php';

class AudioProcessor {
    private $upload_dir;
    private $temp_dir;
    
    public function __construct() {
        $this->upload_dir = AudioConfig::AUDIO_UPLOAD_DIR;
        $this->temp_dir = AudioConfig::AUDIO_TEMP_DIR;
        
        // Создаем директории если их нет
        AudioConfig::ensureUploadDirectory($this->upload_dir);
        AudioConfig::ensureUploadDirectory($this->temp_dir);
    }
    
    /**
     * Обработка загруженного аудиофайла
     * @param array $file Массив $_FILES
     * @param string $vocabulary_id ID словарного слова
     * @return array Результат обработки с путем к файлу или ошибками
     */
    public function processAudioUpload($file, $vocabulary_id = null) {
        $result = [
            'success' => false,
            'audio_path' => null,
            'errors' => []
        ];
        
        // Валидация файла
        $validation_errors = AudioConfig::validateAudio($file);
        if (!empty($validation_errors)) {
            $result['errors'] = $validation_errors;
            return $result;
        }
        
        // Если файл пустой, возвращаем успех без файла
        if ($file['size'] == 0) {
            $result['success'] = true;
            return $result;
        }
        
        try {
            // Генерируем уникальное имя файла
            $new_filename = AudioConfig::generateAudioFilename($file['name']);
            $destination_path = $this->upload_dir . $new_filename;
            
            // Перемещаем файл из временной директории
            if (move_uploaded_file($file['tmp_name'], $destination_path)) {
                // Дополнительная валидация после загрузки
                if ($this->validateUploadedAudio($destination_path)) {
                    $result['success'] = true;
                    $result['audio_path'] = AudioConfig::AUDIO_UPLOAD_PATH . $new_filename;
                } else {
                    // Удаляем файл если валидация не прошла
                    @unlink($destination_path);
                    $result['errors'][] = "Загруженный файл не прошел проверку безопасности.";
                }
            } else {
                $result['errors'][] = "Ошибка при сохранении файла на сервер.";
            }
            
        } catch (Exception $e) {
            $result['errors'][] = "Произошла ошибка при обработке файла: " . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Дополнительная валидация загруженного файла
     * @param string $file_path Путь к файлу
     * @return bool Результат валидации
     */
    private function validateUploadedAudio($file_path) {
        // Проверяем, что файл существует и читается
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }
        
        // Проверяем размер файла
        $file_size = filesize($file_path);
        if ($file_size > AudioConfig::AUDIO_MAX_SIZE || $file_size == 0) {
            return false;
        }
        
        // Проверяем MIME-тип еще раз
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            
            if (!in_array($mime_type, AudioConfig::AUDIO_ALLOWED_MIME_TYPES)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Создание временного аудиофайла
     * @param array $file Массив $_FILES
     * @return array Результат с временным путем
     */
    public function createTempAudio($file) {
        $result = [
            'success' => false,
            'temp_path' => null,
            'errors' => []
        ];
        
        // Валидация файла
        $validation_errors = AudioConfig::validateAudio($file);
        if (!empty($validation_errors)) {
            $result['errors'] = $validation_errors;
            return $result;
        }
        
        if ($file['size'] == 0) {
            $result['success'] = true;
            return $result;
        }
        
        try {
            $temp_filename = 'temp_' . AudioConfig::generateAudioFilename($file['name']);
            $temp_path = $this->temp_dir . $temp_filename;
            
            if (move_uploaded_file($file['tmp_name'], $temp_path)) {
                $result['success'] = true;
                $result['temp_path'] = AudioConfig::AUDIO_TEMP_PATH . $temp_filename;
            } else {
                $result['errors'][] = "Ошибка при создании временного файла.";
            }
            
        } catch (Exception $e) {
            $result['errors'][] = "Ошибка при обработке временного файла: " . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Перемещение временного файла в постоянную директорию
     * @param string $temp_path Путь к временному файлу
     * @return array Результат с постоянным путем
     */
    public function moveTempToAudio($temp_path) {
        $result = [
            'success' => false,
            'audio_path' => null,
            'errors' => []
        ];
        
        if (empty($temp_path)) {
            $result['success'] = true;
            return $result;
        }
        
        $full_temp_path = '../' . $temp_path;
        
        if (!file_exists($full_temp_path)) {
            $result['errors'][] = "Временный файл не найден.";
            return $result;
        }
        
        try {
            $filename = basename($temp_path);
            $new_filename = str_replace('temp_', '', $filename);
            $destination_path = $this->upload_dir . $new_filename;
            
            if (rename($full_temp_path, $destination_path)) {
                $result['success'] = true;
                $result['audio_path'] = AudioConfig::AUDIO_UPLOAD_PATH . $new_filename;
            } else {
                $result['errors'][] = "Ошибка при перемещении файла.";
            }
            
        } catch (Exception $e) {
            $result['errors'][] = "Ошибка при перемещении файла: " . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Удаление аудиофайла
     * @param string $audio_path Путь к аудиофайлу
     * @return bool Результат удаления
     */
    public function deleteAudio($audio_path) {
        return AudioConfig::deleteOldAudio($audio_path);
    }
    
    /**
     * Очистка временных файлов старше определенного времени
     * @param int $max_age Максимальный возраст в секундах (по умолчанию 1 час)
     * @return int Количество удаленных файлов
     */
    public function cleanupTempFiles($max_age = 3600) {
        $deleted_count = 0;
        $current_time = time();
        
        if (is_dir($this->temp_dir)) {
            $files = scandir($this->temp_dir);
            
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') continue;
                
                $file_path = $this->temp_dir . $file;
                $file_age = $current_time - filemtime($file_path);
                
                if ($file_age > $max_age) {
                    if (@unlink($file_path)) {
                        $deleted_count++;
                    }
                }
            }
        }
        
        return $deleted_count;
    }
    
    /**
     * Получение информации об аудиофайле
     * @param string $audio_path Путь к аудиофайлу
     * @return array Информация о файле
     */
    public function getAudioInfo($audio_path) {
        $info = [
            'exists' => false,
            'size' => 0,
            'size_formatted' => '',
            'extension' => '',
            'mime_type' => '',
            'web_path' => ''
        ];
        
        if (empty($audio_path)) {
            return $info;
        }
        
        $full_path = '../' . $audio_path;
        
        if (file_exists($full_path)) {
            $info['exists'] = true;
            $info['size'] = filesize($full_path);
            $info['size_formatted'] = $this->formatFileSize($info['size']);
            $info['extension'] = strtolower(pathinfo($audio_path, PATHINFO_EXTENSION));
            $info['web_path'] = AudioConfig::getWebAudioPath($audio_path);
            
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $info['mime_type'] = finfo_file($finfo, $full_path);
                finfo_close($finfo);
            }
        }
        
        return $info;
    }
    
    /**
     * Форматирование размера файла
     * @param int $size Размер в байтах
     * @return string Отформатированный размер
     */
    private function formatFileSize($size) {
        if ($size >= 1024 * 1024) {
            return round($size / (1024 * 1024), 2) . ' MB';
        } elseif ($size >= 1024) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return $size . ' B';
        }
    }
}
?>
