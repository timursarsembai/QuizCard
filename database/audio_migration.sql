-- SQL команды для добавления поддержки аудиофайлов в QuizCard

-- Добавляем колонку audio_path в таблицу vocabulary
ALTER TABLE vocabulary ADD COLUMN audio_path VARCHAR(500) NULL AFTER image_path;

-- Создаем индекс для улучшения производительности поиска по audio_path
CREATE INDEX idx_vocabulary_audio_path ON vocabulary(audio_path);

-- Обновляем существующие записи (по желанию)
-- UPDATE vocabulary SET audio_path = NULL WHERE audio_path IS NULL;

-- Проверяем результат
-- SELECT id, foreign_word, translation, image_path, audio_path FROM vocabulary LIMIT 5;
