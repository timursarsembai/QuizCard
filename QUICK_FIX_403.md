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

### 🔧 РЕШЕНИЕ для Root пользователя:

Composer показывает предупреждение о запуске от root. Есть несколько вариантов:

#### Вариант 1: Принудительно продолжить (БЫСТРЫЙ):

```bash
# Просто нажмите 'yes' когда спросит:
composer install --no-dev --optimize-autoloader
# При вопросе "Continue as root/super user [yes]?" введите: yes
```

#### Вариант 2: Игнорировать предупреждение (РЕКОМЕНДУЕМЫЙ):

```bash
# Добавьте флаг для игнорирования предупреждения root
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
```

#### Вариант 3: Создать пользователя (для production):

```bash
# Создайте пользователя для сайта
adduser quizcard
chown -R quizcard:quizcard /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/
su - quizcard
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/
composer install --no-dev --optimize-autoloader
```

### ⚡ САМЫЙ БЫСТРЫЙ СПОСОБ:

```bash
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader
```

---

## 🎉 ПРОГРЕСС! Composer установлен, но нужно исправить PSR-4

### Проблема из вывода Composer:

```
Class App\Config\Database located in ./app/Config/database.php does not comply with psr-4 autoloading standard
```

**Причина:** Файлы названы в нижнем регистре (database.php), а должны быть Database.php

### ⚡ БЫСТРОЕ ИСПРАВЛЕНИЕ:

Выполните в SSH:

```bash
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/

# Переименование файлов для PSR-4 соответствия
mv app/Config/database.php app/Config/Database.php
mv app/Config/audio_config.php app/Config/AudioConfig.php
mv app/Config/email_config.php app/Config/EmailConfig.php
mv app/Config/upload_config.php app/Config/UploadConfig.php

# Пересборка autoloader
COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize
```

### 🔧 ИЛИ используйте готовый скрипт:

```bash
wget https://raw.githubusercontent.com/timursarsembai/QuizCard/v.3/fix-psr4-naming.sh
chmod +x fix-psr4-naming.sh
./fix-psr4-naming.sh
```

### ✅ После исправления:

- Файлы будут правильно названы (Database.php, AudioConfig.php, etc.)
- Autoloader заработает корректно
- Ошибка `Class 'App\Config\Database' not found` исчезнет
