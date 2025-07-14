#!/bin/bash
# Быстрое исправление Composer для root пользователя

echo "=== Быстрое исправление Composer ==="

# Переход в директорию проекта
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/

echo "Установка зависимостей с игнорированием предупреждения root..."
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

echo
echo "Проверка результата:"
if [ -f "vendor/autoload.php" ]; then
    echo "✅ vendor/autoload.php создан успешно!"
    ls -la vendor/autoload.php
else
    echo "❌ vendor/autoload.php НЕ СОЗДАН"
    echo "Попробуйте альтернативный способ:"
    echo "curl -sS https://getcomposer.org/installer | php"
    echo "COMPOSER_ALLOW_SUPERUSER=1 php composer.phar install --no-dev --optimize-autoloader"
fi

echo
echo "Проверка .env файла:"
if [ -f ".env" ]; then
    echo "✅ .env найден"
else
    echo "⚠️  .env не найден, копирование из примера..."
    cp .env.example .env 2>/dev/null || echo "❌ .env.example тоже не найден!"
fi

echo
echo "=== ГОТОВО ==="
echo "Теперь проверьте сайт: http://test.sarsembai.com/"
