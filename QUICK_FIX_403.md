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
