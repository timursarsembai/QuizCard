<?php
/**
 * Класс для санитизации входных данных
 */
class Sanitizer {
    
    /**
     * Очистка HTML с сохранением базовых тегов
     */
    public static function html($input, $allowedTags = '<p><br><strong><em><u><ol><ul><li>') {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем потенциально опасные теги и атрибуты
        $input = strip_tags($input, $allowedTags);
        
        // Удаляем javascript: и data: ссылки
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/data:/i', '', $input);
        
        // Удаляем on* события
        $input = preg_replace('/on\w+\s*=/i', '', $input);
        
        return trim($input);
    }

    /**
     * Полная очистка от HTML тегов
     */
    public static function plainText($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем все HTML теги
        $input = strip_tags($input);
        
        // Декодируем HTML сущности
        $input = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
        
        // Удаляем лишние пробелы
        $input = preg_replace('/\s+/', ' ', $input);
        
        return trim($input);
    }

    /**
     * Очистка для имени пользователя
     */
    public static function username($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем все, кроме букв, цифр, дефиса и подчеркивания
        $input = preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
        
        return trim($input);
    }

    /**
     * Очистка email
     */
    public static function email($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем пробелы и приводим к нижнему регистру
        $input = strtolower(trim($input));
        
        // Фильтруем email
        $input = filter_var($input, FILTER_SANITIZE_EMAIL);
        
        return $input;
    }

    /**
     * Очистка имени (ФИО)
     */
    public static function name($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем HTML теги
        $input = strip_tags($input);
        
        // Оставляем только буквы, пробелы, дефисы и апострофы
        $input = preg_replace('/[^a-zA-ZА-Яа-яЁё\s\'-]/u', '', $input);
        
        // Удаляем лишние пробелы
        $input = preg_replace('/\s+/', ' ', $input);
        
        return trim($input);
    }

    /**
     * Очистка числовых значений
     */
    public static function number($input, $type = 'int') {
        if (is_null($input) || $input === '') {
            return null;
        }
        
        if ($type === 'float') {
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        }
        
        return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Очистка URL
     */
    public static function url($input) {
        if (is_null($input)) {
            return null;
        }
        
        $input = filter_var($input, FILTER_SANITIZE_URL);
        
        // Проверяем на допустимые протоколы
        $allowedProtocols = ['http', 'https', 'ftp'];
        $protocol = parse_url($input, PHP_URL_SCHEME);
        
        if ($protocol && !in_array($protocol, $allowedProtocols)) {
            return null;
        }
        
        return $input;
    }

    /**
     * Очистка для поиска
     */
    public static function search($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем HTML теги
        $input = strip_tags($input);
        
        // Удаляем специальные символы SQL
        $input = str_replace(['%', '_'], ['\\%', '\\_'], $input);
        
        // Удаляем лишние пробелы
        $input = preg_replace('/\s+/', ' ', $input);
        
        return trim($input);
    }

    /**
     * Очистка файлового пути
     */
    public static function filename($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем опасные символы для файловой системы
        $input = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
        
        // Удаляем точки в начале (скрытые файлы)
        $input = ltrim($input, '.');
        
        // Ограничиваем длину
        if (strlen($input) > 255) {
            $input = substr($input, 0, 255);
        }
        
        return $input;
    }

    /**
     * Общая очистка строки
     */
    public static function string($input, $maxLength = null) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем управляющие символы
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Нормализуем пробелы
        $input = preg_replace('/\s+/', ' ', $input);
        
        $input = trim($input);
        
        // Ограничиваем длину если указано
        if ($maxLength && strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }

    /**
     * Очистка массива
     */
    public static function array($input, $sanitizeFunction = 'plainText') {
        if (!is_array($input)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($input as $key => $value) {
            $cleanKey = self::string($key);
            
            if (is_array($value)) {
                $sanitized[$cleanKey] = self::array($value, $sanitizeFunction);
            } else {
                $sanitized[$cleanKey] = self::$sanitizeFunction($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Санитизация для SQL LIKE запросов
     */
    public static function sqlLike($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Экранируем специальные символы LIKE
        $input = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $input);
        
        return self::string($input);
    }

    /**
     * Очистка JSON данных
     */
    public static function json($input) {
        if (is_null($input)) {
            return null;
        }
        
        // Удаляем управляющие символы, которые могут нарушить JSON
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        return $input;
    }

    /**
     * Очистка для безопасного вывода в HTML
     */
    public static function output($input, $encoding = 'UTF-8') {
        if (is_null($input)) {
            return '';
        }
        
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, $encoding);
    }

    /**
     * Очистка для безопасного вывода в JavaScript
     */
    public static function js($input) {
        if (is_null($input)) {
            return '';
        }
        
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Очистка всех данных POST/GET
     */
    public static function requestData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $cleanKey = self::string($key);
            
            if (is_array($value)) {
                $sanitized[$cleanKey] = self::array($value);
            } else {
                $sanitized[$cleanKey] = self::string($value);
            }
        }
        
        return $sanitized;
    }
}
?>
