# Руководство по развертыванию QuizCard на VPS

## Диагностика и решение ошибки 403 Forbidden

### 1. Проверка структуры файлов на VPS

Выполните на сервере:

```bash
ls -la /path/to/your/website/
ls -la /path/to/your/website/public/
```

### 2. Настройка прав доступа к файлам

```bash
# Перейти в корень проекта
cd /path/to/your/website/

# Установить права на файлы
find . -type f -exec chmod 644 {} \;

# Установить права на директории
find . -type d -exec chmod 755 {} \;

# Особые права для логов и uploads
chmod 755 logs/
chmod 777 logs/
chmod 755 public/uploads/
chmod 777 public/uploads/
chmod 777 public/uploads/audio/
chmod 777 public/uploads/vocabulary/
```

### 3. Настройка Apache Virtual Host

Создайте файл `/etc/apache2/sites-available/test.sarsembai.com.conf`:

```apache
<VirtualHost *:80>
    ServerName test.sarsembai.com
    DocumentRoot /path/to/your/website/public

    <Directory /path/to/your/website/public>
        AllowOverride All
        Require all granted
        Options -Indexes
        DirectoryIndex index.php
    </Directory>

    <Directory /path/to/your/website>
        AllowOverride None
        Require all denied
    </Directory>

    # Логи
    ErrorLog ${APACHE_LOG_DIR}/test.sarsembai.com_error.log
    CustomLog ${APACHE_LOG_DIR}/test.sarsembai.com_access.log combined
</VirtualHost>
```

Активируйте сайт:

```bash
sudo a2ensite test.sarsembai.com.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 4. Проверка .htaccess

Убедитесь, что в `public/.htaccess` есть базовые правила:

```apache
RewriteEngine On

# Если файл или директория не существует, направить на index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Защита от прямого доступа к файлам app/
RewriteRule ^app/ - [F,L]
```

### 5. Настройка базы данных

```bash
# Войти в MySQL
mysql -u root -p

# Создать базу данных
CREATE DATABASE quizcard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Создать пользователя
CREATE USER 'quizcard_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON quizcard_db.* TO 'quizcard_user'@'localhost';
FLUSH PRIVILEGES;

# Импортировать структуру
mysql -u quizcard_user -p quizcard_db < database/setup.sql
```

### 6. Настройка .env файла

```env
# Database
DB_HOST=localhost
DB_NAME=quizcard_db
DB_USER=quizcard_user
DB_PASS=your_password

# Security
SECURITY_KEY=your_random_32_char_key_here
CSRF_SECRET=your_random_csrf_secret_here

# Email (опционально)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM=your_email@gmail.com
```

### 7. Установка зависимостей Composer

```bash
cd /path/to/your/website/
composer install --no-dev --optimize-autoloader
```

### 8. Проверка логов Apache

```bash
# Проверить логи ошибок
sudo tail -f /var/log/apache2/test.sarsembai.com_error.log

# Или общие логи
sudo tail -f /var/log/apache2/error.log
```

### 9. Тестирование

```bash
# Проверить синтаксис PHP
php -l public/index.php

# Проверить доступность
curl -I http://test.sarsembai.com/
```

## Быстрое решение для FastPanel

Если используете FastPanel:

1. **Настройка домена:**

   - Убедитесь, что DocumentRoot указывает на папку `public/`
   - Включите поддержку .htaccess (AllowOverride All)

2. **Права доступа через FastPanel:**

   - Установите права 755 на директории
   - Установите права 644 на файлы
   - Для uploads и logs: 777

3. **PHP модули:**
   - Убедитесь что включены: mysqli, json, mbstring, fileinfo

## Альтернативный .htaccess для shared hosting

Если стандартный .htaccess не работает, создайте упрощенную версию в `public/.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Базовая защита
Options -Indexes
```

## Диагностика проблем

### Проблема: 403 Forbidden

- Проверьте права доступа к файлам
- Убедитесь что DocumentRoot указывает на `public/`
- Проверьте что Apache может читать .htaccess

### Проблема: 500 Internal Server Error

- Проверьте логи Apache
- Убедитесь что все PHP модули установлены
- Проверьте синтаксис .htaccess

### Проблема: Белая страница

- Проверьте логи PHP
- Убедитесь что .env файл настроен правильно
- Проверьте подключение к базе данных

## 🚨 СРОЧНОЕ РЕШЕНИЕ ПРОБЛЕМЫ 403

### Анализ ваших логов:

```
AH01276: Cannot serve directory /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/:
No matching DirectoryIndex (index.php,index.html) found
```

**ПРОБЛЕМА:** DocumentRoot указывает на корень проекта вместо папки `public/`

### Немедленное решение:

#### Вариант 1: Изменить DocumentRoot в FastPanel (РЕКОМЕНДУЕТСЯ)

1. Зайдите в FastPanel → Домены → test.sarsembai.com
2. Найдите настройку "Корневая папка" или "DocumentRoot"
3. Измените с `/var/www/sarsembai_co_usr/data/www/sarsembai.com/test/`
   на `/var/www/sarsembai_co_usr/data/www/sarsembai.com/test/public/`
4. Сохраните настройки

#### Вариант 2: Временное решение - создать index.php в корне

Если не можете изменить DocumentRoot, создайте файл в корне проекта:

```php
<?php
// /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/index.php
// Временное перенаправление на public/
header('Location: public/');
exit;
```

#### Вариант 3: Через SSH (если есть доступ)

```bash
# Подключитесь по SSH и выполните:
cd /var/www/sarsembai_co_usr/data/www/sarsembai.com/test/

# Проверьте структуру
ls -la
ls -la public/

# Создайте временный index.php если нужно
echo '<?php header("Location: public/"); exit; ?>' > index.php
```

### После исправления DocumentRoot проверьте:

```bash
# Права доступа к public/
chmod 755 public/
chmod 644 public/index.php

# Убедитесь что .htaccess существует в public/
ls -la public/.htaccess

# Если нет - создайте минимальный:
cat > public/.htaccess << 'EOF'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
Options -Indexes
EOF
```

## Контрольный список для deployment

- [ ] Права доступа к файлам настроены
- [ ] DocumentRoot указывает на public/
- [ ] AllowOverride All включен
- [ ] Модуль rewrite включен
- [ ] База данных создана и настроена
- [ ] .env файл настроен
- [ ] Composer dependencies установлены
- [ ] Логи доступны для записи
- [ ] Uploads директория доступна для записи
