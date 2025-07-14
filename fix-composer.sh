#!/bin/bash

echo "=== QuizCard Composer Fix Script ==="
echo "Исправление ошибки: Class 'App\\Config\\Database' not found"
echo

# Проверка текущей директории
echo "1. Проверка текущей директории:"
pwd
echo

# Проверка наличия composer.json
echo "2. Проверка composer.json:"
if [ -f "composer.json" ]; then
    echo "✅ composer.json найден"
    cat composer.json
else
    echo "❌ composer.json НЕ НАЙДЕН!"
    echo "Создание базового composer.json..."
    cat > composer.json << 'EOF'
{
    "name": "quizcard/app",
    "type": "project",
    "require": {
        "php": ">=7.4"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
EOF
    echo "✅ composer.json создан"
fi
echo

# Проверка Composer
echo "3. Проверка Composer:"
if command -v composer &> /dev/null; then
    echo "✅ Composer найден в системе"
    composer --version
    COMPOSER_CMD="composer"
elif [ -f "composer.phar" ]; then
    echo "✅ Найден локальный composer.phar"
    php composer.phar --version
    COMPOSER_CMD="php composer.phar"
else
    echo "❌ Composer не найден. Скачиваем..."
    curl -sS https://getcomposer.org/installer | php
    if [ -f "composer.phar" ]; then
        echo "✅ Composer скачан"
        COMPOSER_CMD="php composer.phar"
    else
        echo "❌ Не удалось скачать Composer"
        exit 1
    fi
fi
echo

# Установка зависимостей
echo "4. Установка зависимостей:"
$COMPOSER_CMD install --no-dev --optimize-autoloader
echo

# Проверка autoloader
echo "5. Проверка autoloader:"
if [ -f "vendor/autoload.php" ]; then
    echo "✅ vendor/autoload.php создан"
    ls -la vendor/autoload.php
else
    echo "❌ vendor/autoload.php НЕ СОЗДАН!"
    echo "Попытка пересоздания..."
    $COMPOSER_CMD dump-autoload --optimize
fi
echo

# Проверка .env
echo "6. Проверка .env файла:"
if [ -f ".env" ]; then
    echo "✅ .env найден"
    echo "Проверка настроек БД:"
    grep -E "^(DB_HOST|DB_NAME|DB_USER|DB_PASS)" .env || echo "⚠️  Настройки БД не найдены в .env"
else
    echo "❌ .env НЕ НАЙДЕН!"
    if [ -f ".env.example" ]; then
        echo "Копирование из .env.example..."
        cp .env.example .env
        echo "✅ .env создан из примера"
        echo "⚠️  ОБЯЗАТЕЛЬНО настройте данные БД в .env файле!"
    else
        echo "Создание базового .env..."
        cat > .env << 'EOF'
# Database
DB_HOST=localhost
DB_NAME=quizcard_db
DB_USER=your_db_user
DB_PASS=your_db_password

# Security
SECURITY_KEY=your_random_32_char_key_here_12345
CSRF_SECRET=your_random_csrf_secret_here_67890
EOF
        echo "✅ Базовый .env создан"
        echo "⚠️  ОБЯЗАТЕЛЬНО настройте данные БД!"
    fi
fi
echo

# Проверка структуры app/
echo "7. Проверка структуры app/:"
if [ -d "app" ]; then
    echo "✅ Директория app/ найдена"
    echo "Содержимое app/:"
    ls -la app/
    echo
    if [ -d "app/Config" ]; then
        echo "✅ app/Config/ найдена"
        ls -la app/Config/
    else
        echo "❌ app/Config/ НЕ НАЙДЕНА!"
    fi
else
    echo "❌ Директория app/ НЕ НАЙДЕНА!"
fi
echo

# Проверка прав доступа
echo "8. Проверка прав доступа:"
echo "vendor/:"
ls -ld vendor/ 2>/dev/null || echo "vendor/ не найдена"
echo "app/:"
ls -ld app/ 2>/dev/null || echo "app/ не найдена"
echo "public/:"
ls -ld public/ 2>/dev/null || echo "public/ не найдена"
echo

# Тест автозагрузки
echo "9. Тест автозагрузки:"
if [ -f "vendor/autoload.php" ]; then
    php -r "
    require_once 'vendor/autoload.php';
    if (class_exists('App\\Config\\Database')) {
        echo '✅ Класс App\\Config\\Database загружается корректно\n';
    } else {
        echo '❌ Класс App\\Config\\Database НЕ НАЙДЕН!\n';
        echo 'Проверьте файл app/Config/Database.php\n';
    }
    "
else
    echo "❌ vendor/autoload.php не найден, тест пропущен"
fi
echo

echo "=== РЕЗУЛЬТАТЫ ==="
echo "После выполнения этого скрипта:"
echo "1. Обновите страницу: http://test.sarsembai.com/"
echo "2. Если ошибка 500 осталась, проверьте логи PHP"
echo "3. Убедитесь что настройки БД в .env корректны"
echo
echo "Для проверки логов:"
echo "tail -f /var/log/apache2/error.log"
echo
echo "=== ГОТОВО ==="
