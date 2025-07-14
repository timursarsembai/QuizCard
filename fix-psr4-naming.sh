#!/bin/bash
# Исправление именования файлов для PSR-4

echo "=== Исправление PSR-4 соответствия ==="

cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/

echo "Переименование файлов в app/Config/ для соответствия PSR-4..."

# Переименование файлов конфигурации
if [ -f "app/Config/database.php" ]; then
    mv app/Config/database.php app/Config/Database.php
    echo "✅ database.php → Database.php"
fi

if [ -f "app/Config/audio_config.php" ]; then
    mv app/Config/audio_config.php app/Config/AudioConfig.php
    echo "✅ audio_config.php → AudioConfig.php"
fi

if [ -f "app/Config/email_config.php" ]; then
    mv app/Config/email_config.php app/Config/EmailConfig.php
    echo "✅ email_config.php → EmailConfig.php"
fi

if [ -f "app/Config/upload_config.php" ]; then
    mv app/Config/upload_config.php app/Config/UploadConfig.php
    echo "✅ upload_config.php → UploadConfig.php"
fi

echo
echo "Проверка имен классов в файлах..."

# Исправление имен классов в файлах если нужно
if [ -f "app/Config/AudioConfig.php" ]; then
    if grep -q "class AudioConfig" app/Config/AudioConfig.php; then
        echo "✅ AudioConfig класс правильно назван"
    else
        echo "⚠️  Исправление имени класса в AudioConfig.php"
        sed -i 's/class audioconfig/class AudioConfig/ig' app/Config/AudioConfig.php
    fi
fi

if [ -f "app/Config/EmailConfig.php" ]; then
    if grep -q "class EmailConfig" app/Config/EmailConfig.php; then
        echo "✅ EmailConfig класс правильно назван"
    else
        echo "⚠️  Исправление имени класса в EmailConfig.php"
        sed -i 's/class emailconfig/class EmailConfig/ig' app/Config/EmailConfig.php
    fi
fi

if [ -f "app/Config/UploadConfig.php" ]; then
    if grep -q "class UploadConfig" app/Config/UploadConfig.php; then
        echo "✅ UploadConfig класс правильно назван"
    else
        echo "⚠️  Исправление имени класса в UploadConfig.php"
        sed -i 's/class uploadconfig/class UploadConfig/ig' app/Config/UploadConfig.php
    fi
fi

echo
echo "Пересборка autoloader..."
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize

echo
echo "Финальная проверка:"
if [ -f "vendor/autoload.php" ]; then
    echo "✅ vendor/autoload.php найден"
    
    echo "Тест загрузки классов:"
    php -r "
    require_once 'vendor/autoload.php';
    
    \$classes = ['App\\\\Config\\\\Database', 'App\\\\Config\\\\AudioConfig', 'App\\\\Config\\\\EmailConfig', 'App\\\\Config\\\\UploadConfig'];
    
    foreach (\$classes as \$class) {
        if (class_exists(\$class)) {
            echo '✅ ' . \$class . ' загружен успешно\\n';
        } else {
            echo '❌ ' . \$class . ' НЕ НАЙДЕН\\n';
        }
    }
    "
else
    echo "❌ vendor/autoload.php отсутствует"
fi

echo
echo "Структура app/Config/ после исправления:"
ls -la app/Config/

echo
echo "=== ГОТОВО ==="
echo "Теперь проверьте сайт: http://test.sarsembai.com/"
