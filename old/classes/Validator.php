<?php
/**
 * Класс для валидации входных данных
 */
class Validator {
    private $errors = [];
    private $data = [];

    public function __construct($data = []) {
        $this->data = $data;
    }

    /**
     * Добавить ошибку
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Валидация email
     */
    public function email($field, $required = true) {
        $value = $this->data[$field] ?? null;

        if ($required && empty($value)) {
            $this->addError($field, 'Email обязателен для заполнения');
            return $this;
        }

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Некорректный формат email');
        }

        if (!empty($value) && strlen($value) > 320) {
            $this->addError($field, 'Email слишком длинный');
        }

        return $this;
    }

    /**
     * Валидация имени пользователя
     */
    public function username($field, $minLength = 3, $maxLength = 50) {
        $value = $this->data[$field] ?? null;

        if (empty($value)) {
            $this->addError($field, 'Имя пользователя обязательно для заполнения');
            return $this;
        }

        if (strlen($value) < $minLength) {
            $this->addError($field, "Имя пользователя должно содержать минимум {$minLength} символов");
        }

        if (strlen($value) > $maxLength) {
            $this->addError($field, "Имя пользователя должно содержать максимум {$maxLength} символов");
        }

        // Проверяем на допустимые символы (буквы, цифры, подчеркивание, дефис)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->addError($field, 'Имя пользователя может содержать только буквы, цифры, дефис и подчеркивание');
        }

        return $this;
    }

    /**
     * Валидация пароля
     */
    public function password($field, $minLength = 8, $requireSpecial = true) {
        $value = $this->data[$field] ?? null;

        if (empty($value)) {
            $this->addError($field, 'Пароль обязателен для заполнения');
            return $this;
        }

        if (strlen($value) < $minLength) {
            $this->addError($field, "Пароль должен содержать минимум {$minLength} символов");
        }

        if (strlen($value) > 128) {
            $this->addError($field, 'Пароль слишком длинный (максимум 128 символов)');
        }

        // Проверяем на наличие букв и цифр
        if (!preg_match('/[a-zA-Z]/', $value)) {
            $this->addError($field, 'Пароль должен содержать хотя бы одну букву');
        }

        if (!preg_match('/[0-9]/', $value)) {
            $this->addError($field, 'Пароль должен содержать хотя бы одну цифру');
        }

        if ($requireSpecial && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->addError($field, 'Пароль должен содержать хотя бы один специальный символ');
        }

        return $this;
    }

    /**
     * Валидация имени (ФИО)
     */
    public function name($field, $minLength = 2, $maxLength = 100, $required = true) {
        $value = $this->data[$field] ?? null;

        if ($required && empty($value)) {
            $this->addError($field, 'Имя обязательно для заполнения');
            return $this;
        }

        if (!empty($value)) {
            if (strlen($value) < $minLength) {
                $this->addError($field, "Имя должно содержать минимум {$minLength} символов");
            }

            if (strlen($value) > $maxLength) {
                $this->addError($field, "Имя слишком длинное (максимум {$maxLength} символов)");
            }

            // Проверяем на HTML теги
            if ($value !== strip_tags($value)) {
                $this->addError($field, 'Имя не может содержать HTML теги');
            }

            // Проверяем на допустимые символы (буквы, пробелы, дефисы, апострофы)
            if (!preg_match('/^[a-zA-ZА-Яа-яЁё\s\'-]+$/u', $value)) {
                $this->addError($field, 'Имя может содержать только буквы, пробелы, дефисы и апострофы');
            }
        }

        return $this;
    }

    /**
     * Валидация текста (описания, комментарии)
     */
    public function text($field, $maxLength = 1000, $required = false) {
        $value = $this->data[$field] ?? null;

        if ($required && empty($value)) {
            $this->addError($field, 'Поле обязательно для заполнения');
            return $this;
        }

        if (!empty($value)) {
            if (strlen($value) > $maxLength) {
                $this->addError($field, "Текст слишком длинный (максимум {$maxLength} символов)");
            }

            // Проверяем на потенциально опасные теги
            $dangerousTags = ['<script', '<iframe', '<object', '<embed', '<form'];
            foreach ($dangerousTags as $tag) {
                if (stripos($value, $tag) !== false) {
                    $this->addError($field, 'Текст содержит недопустимые элементы');
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Валидация числа
     */
    public function number($field, $min = null, $max = null, $required = true) {
        $value = $this->data[$field] ?? null;

        if ($required && ($value === null || $value === '')) {
            $this->addError($field, 'Числовое значение обязательно для заполнения');
            return $this;
        }

        if ($value !== null && $value !== '') {
            if (!is_numeric($value)) {
                $this->addError($field, 'Значение должно быть числом');
            } else {
                $numValue = (float)$value;
                
                if ($min !== null && $numValue < $min) {
                    $this->addError($field, "Значение должно быть не менее {$min}");
                }

                if ($max !== null && $numValue > $max) {
                    $this->addError($field, "Значение должно быть не более {$max}");
                }
            }
        }

        return $this;
    }

    /**
     * Валидация CSRF токена
     */
    public function csrf($token = null) {
        require_once dirname(__DIR__) . '/classes/CSRFProtection.php';
        
        if ($token === null) {
            $token = $this->data['csrf_token'] ?? null;
        }

        if (!CSRFProtection::validateToken($token)) {
            $this->addError('csrf_token', 'Недействительный CSRF токен');
        }

        return $this;
    }

    /**
     * Кастомная валидация
     */
    public function custom($field, $callback, $message = 'Недопустимое значение') {
        $value = $this->data[$field] ?? null;

        if (!$callback($value)) {
            $this->addError($field, $message);
        }

        return $this;
    }

    /**
     * Проверка на совпадение полей (например, пароль и подтверждение)
     */
    public function matches($field, $matchField, $message = null) {
        $value1 = $this->data[$field] ?? null;
        $value2 = $this->data[$matchField] ?? null;

        if ($value1 !== $value2) {
            $message = $message ?: 'Поля не совпадают';
            $this->addError($field, $message);
        }

        return $this;
    }

    /**
     * Валидация уникальности (для проверки в БД)
     */
    public function unique($field, $table, $column, $db, $excludeId = null) {
        $value = $this->data[$field] ?? null;

        if (!empty($value)) {
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];

            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $this->addError($field, 'Это значение уже используется');
            }
        }

        return $this;
    }

    /**
     * Проверить валидность
     */
    public function isValid() {
        return empty($this->errors);
    }

    /**
     * Получить ошибки
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Получить первую ошибку для поля
     */
    public function getFirstError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field][0] : null;
    }

    /**
     * Получить все ошибки как строку
     */
    public function getErrorsAsString($separator = ', ') {
        $allErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return implode($separator, $allErrors);
    }

    /**
     * Статический метод для быстрой валидации
     */
    public static function make($data) {
        return new self($data);
    }
}
?>
