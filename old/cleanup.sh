#!/bin/bash

# Скрипт автоматической очистки ненужных файлов в проекте QuizCard
# Запускается для удаления временных и отладочных файлов

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "🧹 Запуск очистки проекта QuizCard..."

# Счетчик удаленных файлов
DELETED_COUNT=0

# Удаляем .DS_Store файлы (macOS)
if find "$PROJECT_ROOT" -name ".DS_Store" -type f 2>/dev/null | grep -q .; then
    echo "Удаляем .DS_Store файлы..."
    find "$PROJECT_ROOT" -name ".DS_Store" -type f -delete
    DELETED_COUNT=$((DELETED_COUNT + $(find "$PROJECT_ROOT" -name ".DS_Store" -type f 2>/dev/null | wc -l)))
fi

# Удаляем временные файлы редакторов
TEMP_PATTERNS=("*.swp" "*~" "*.backup" "*.tmp" "*.bak" "*.old")
for pattern in "${TEMP_PATTERNS[@]}"; do
    if find "$PROJECT_ROOT" -name "$pattern" -type f 2>/dev/null | grep -q .; then
        echo "Удаляем файлы: $pattern"
        COUNT=$(find "$PROJECT_ROOT" -name "$pattern" -type f 2>/dev/null | wc -l)
        find "$PROJECT_ROOT" -name "$pattern" -type f -delete
        DELETED_COUNT=$((DELETED_COUNT + COUNT))
    fi
done

# Очищаем папку temp в uploads (если есть файлы)
TEMP_DIR="$PROJECT_ROOT/uploads/audio/temp"
if [ -d "$TEMP_DIR" ]; then
    TEMP_FILES=$(find "$TEMP_DIR" -type f ! -name ".htaccess" 2>/dev/null | wc -l)
    if [ "$TEMP_FILES" -gt 0 ]; then
        echo "Очищаем временную папку uploads/audio/temp..."
        find "$TEMP_DIR" -type f ! -name ".htaccess" -delete
        DELETED_COUNT=$((DELETED_COUNT + TEMP_FILES))
    fi
fi

# Проверяем размер логов и предупреждаем, если они большие
LOG_DIR="$PROJECT_ROOT/logs"
if [ -d "$LOG_DIR" ]; then
    for log_file in "$LOG_DIR"/*.log; do
        if [ -f "$log_file" ]; then
            SIZE=$(stat -f%z "$log_file" 2>/dev/null || stat -c%s "$log_file" 2>/dev/null)
            if [ "$SIZE" -gt 10485760 ]; then # 10MB
                echo "⚠️  Внимание: Лог-файл $(basename "$log_file") имеет размер больше 10MB"
                echo "   Рассмотрите возможность ротации логов через SecurityLogger::rotateLogFile()"
            fi
        fi
    done
fi

# Проверяем наличие отладочных функций в коде
echo "🔍 Проверяем наличие отладочного кода..."
DEBUG_PATTERNS=("var_dump(" "print_r(" "echo.*DEBUG" "console.log.*debug")
DEBUG_FOUND=false

for pattern in "${DEBUG_PATTERNS[@]}"; do
    if grep -r --include="*.php" --include="*.js" "$pattern" "$PROJECT_ROOT" 2>/dev/null | grep -v "cleanup.sh" | grep -q .; then
        if [ "$DEBUG_FOUND" = false ]; then
            echo "⚠️  Найден отладочный код:"
            DEBUG_FOUND=true
        fi
        grep -r --include="*.php" --include="*.js" "$pattern" "$PROJECT_ROOT" 2>/dev/null | grep -v "cleanup.sh" | head -5
    fi
done

if [ "$DEBUG_FOUND" = false ]; then
    echo "✅ Отладочный код не найден"
fi

# Проверяем наличие возможных дубликатов
echo "🔍 Проверяем наличие дубликатов файлов..."
DUPLICATES_FOUND=false

# Проверяем файлы с подозрительными названиями
SUSPICIOUS_PATTERNS=("*-new.*" "*-old.*" "*-backup.*" "*-copy.*" "*_new.*" "*_old.*" "*_backup.*" "*_copy.*")
for pattern in "${SUSPICIOUS_PATTERNS[@]}"; do
    if find "$PROJECT_ROOT" -name "$pattern" -type f 2>/dev/null | grep -q .; then
        if [ "$DUPLICATES_FOUND" = false ]; then
            echo "⚠️  Найдены подозрительные файлы (возможные дубликаты):"
            DUPLICATES_FOUND=true
        fi
        find "$PROJECT_ROOT" -name "$pattern" -type f 2>/dev/null
    fi
done

# Проверяем PHP файлы с одинаковыми MD5 хешами
DUPLICATE_HASHES=$(find "$PROJECT_ROOT" -name "*.php" -exec md5 {} \; 2>/dev/null | awk '{print $4}' | sort | uniq -d)
if [ -n "$DUPLICATE_HASHES" ]; then
    if [ "$DUPLICATES_FOUND" = false ]; then
        echo "⚠️  Найдены файлы с одинаковым содержимым:"
        DUPLICATES_FOUND=true
    fi
    for hash in $DUPLICATE_HASHES; do
        echo "Дубликаты с хешем $hash:"
        find "$PROJECT_ROOT" -name "*.php" -exec md5 {} \; 2>/dev/null | grep "$hash"
    done
fi

if [ "$DUPLICATES_FOUND" = false ]; then
    echo "✅ Дубликаты файлов не найдены"
fi

echo ""
echo "🎉 Очистка завершена!"
echo "📊 Удалено файлов: $DELETED_COUNT"
echo "💾 Текущий размер проекта: $(du -sh "$PROJECT_ROOT" | cut -f1)"
echo ""

if [ "$DELETED_COUNT" -eq 0 ]; then
    echo "✨ Проект уже чист!"
else
    echo "🧹 Проект очищен от ненужных файлов"
fi
