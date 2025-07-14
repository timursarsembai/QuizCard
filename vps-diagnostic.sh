#!/bin/bash

echo "=== QuizCard VPS Диагностика ==="
echo "Дата: $(date)"
echo

# Проверка текущей директории
echo "1. Текущая директория и файлы:"
pwd
ls -la
echo

# Проверка public директории
echo "2. Содержимое public/:"
ls -la public/
echo

# Проверка app/Config/Database.php
echo "3. Проверка файла Database.php:"
if [ -f "app/Config/Database.php" ]; then
    echo "✅ app/Config/Database.php найден"
    echo "Первые 5 строк:"
    head -5 app/Config/Database.php
else
    echo "❌ app/Config/Database.php НЕ НАЙДЕН!"
fi
echo

# Проверка composer.json
echo "4. Проверка composer.json:"
if [ -f "composer.json" ]; then
    echo "✅ composer.json найден"
    cat composer.json
else
    echo "❌ composer.json НЕ НАЙДЕН!"
    echo "Создание composer.json..."
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

# Проверка PHP
echo "4. Версия PHP:"
php -v | head -1
echo

# Проверка синтаксиса
echo "5. Проверка синтаксиса PHP:"
php -l public/index.php
echo

# Проверка модулей PHP
echo "6. Важные PHP модули:"
php -m | grep -E "(mysqli|pdo|json|mbstring|fileinfo|openssl)" || echo "Некоторые модули отсутствуют"
echo

# Проверка Apache модулей
echo "7. Apache модули (если доступно):"
apache2ctl -M 2>/dev/null | grep -E "(rewrite|headers)" || echo "Проверьте apache2ctl или используйте панель управления"
echo

# Проверка composer
echo "8. Composer и зависимости:"
which composer 2>/dev/null || echo "Composer не найден в PATH"
if [ -f "vendor/autoload.php" ]; then
    echo "vendor/autoload.php найден"
else
    echo "vendor/autoload.php НЕ НАЙДЕН! Запустите: composer install"
fi
echo

# Проверка подключения к БД
echo "9. Проверка .env и подключения к БД:"
if [ -f ".env" ]; then
    echo ".env найден"
    grep -E "^(DB_HOST|DB_NAME|DB_USER)" .env 2>/dev/null || echo "Настройки БД в .env не найдены"
else
    echo ".env НЕ НАЙДЕН!"
fi
echo

# Проверка логов
echo "10. Последние ошибки Apache (если доступно):"
tail -5 /var/log/apache2/error.log 2>/dev/null || echo "Логи Apache недоступны через этот путь"
echo

# Проверка директорий для записи
echo "11. Проверка директорий для записи:"
test -w logs/ && echo "logs/ - доступна для записи" || echo "logs/ - НЕ доступна для записи"
test -w public/uploads/ && echo "public/uploads/ - доступна для записи" || echo "public/uploads/ - НЕ доступна для записи"
echo

echo "=== Рекомендации ==="
echo "Если видите ошибки выше:"
echo "1. Установите права: chmod 755 public/ && chmod 644 public/index.php"
echo "2. Если vendor/ отсутствует: composer install"
echo "3. Если .env отсутствует: скопируйте из .env.example"
echo "4. Убедитесь что DocumentRoot указывает на public/"
echo "5. Проверьте что AllowOverride All включен в Apache"
echo

echo "=== Быстрые команды для исправления ==="
echo "# Права доступа:"
echo "find . -type f -exec chmod 644 {} \\;"
echo "find . -type d -exec chmod 755 {} \\;"
echo "chmod 777 logs/ public/uploads/"
echo
echo "# Если нужен простой .htaccess:"
echo "cp public/.htaccess.simple public/.htaccess"
echo
echo "# Проверить соединение:"
echo "curl -I http://$(hostname -I | awk '{print \$1}')/"
