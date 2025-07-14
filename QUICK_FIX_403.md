# БЫСТРОЕ ИСПРАВЛЕНИЕ 403 ОШИБКИ

## Проблема определена из ваших логов:

DocumentRoot указывает на корень проекта вместо папки `public/`

## 🚀 Решение за 2 минуты:

### 1. В FastPanel:

- Перейдите к настройкам домена test.sarsembai.com
- Найдите "Корневая папка" или "DocumentRoot"
- Измените путь: добавьте `/public` в конец
- Было: `/var/www/sarsembai_co_usr/data/www/sarsembai.com/test/`
- Стало: `/var/www/sarsembai_co_usr/data/www/sarsembai.com/test/public/`

### 2. Альтернатива (если не можете изменить DocumentRoot):

По SSH создайте файл в корне:

```bash
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/
cat > index.php << 'EOF'
<?php header('Location: public/'); exit; ?>
EOF
```

### 3. Проверьте права:

```bash
chmod 755 public/
chmod 644 public/index.php
```

### 4. Убедитесь что есть .htaccess в public/:

```bash
ls -la public/.htaccess
```

Если нет, создайте:

```bash
cat > public/.htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
Options -Indexes
EOF
```

## После исправления сайт должен работать!

Проверьте: http://test.sarsembai.com/

---

# 🎉 ПРОГРЕСС! 403 → 500 (это хорошо!)

Ошибка 403 исправлена! Теперь Apache находит и запускает PHP файлы.

### Новая проблема из логов:

```
PHP Fatal error: Class 'App\Config\Database' not found in Router.php:10
```

**Причина:** Composer autoloader не установлен или не работает.

## 🚀 РЕШЕНИЕ ОШИБКИ 500:

### 1. Установите зависимости Composer по SSH:

```bash
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/

# Проверьте наличие composer.json
ls -la composer.json

# Установите зависимости
composer install --no-dev --optimize-autoloader

# Проверьте что создался vendor/autoload.php
ls -la vendor/autoload.php
```

### 2. Если composer не найден глобально:

```bash
# Скачайте composer локально
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### 3. Проверьте файл .env:

```bash
# Убедитесь что .env существует
ls -la .env

# Если нет, скопируйте из примера
cp .env.example .env
```

### 4. Настройте .env для вашей БД:

```bash
nano .env
```

Убедитесь что указаны правильные данные БД:

```env
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_db_user
DB_PASS=your_db_password
```
