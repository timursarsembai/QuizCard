-- Полная инициализация базы данных QuizCard
-- Включает все таблицы и функции: основные, email верификация, аудио, тесты

CREATE DATABASE IF NOT EXISTS quizcard_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quizcard_db;

-- Таблица пользователей (преподаватели и ученики)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('teacher', 'student') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    teacher_id INT NULL,
    
    -- Поля для верификации email
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) NULL,
    verification_token_expires DATETIME NULL,
    last_verification_sent DATETIME NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица колод (тем)
CREATE TABLE decks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    color VARCHAR(7) DEFAULT '#667eea',
    daily_word_limit INT DEFAULT 20 COMMENT 'Лимит новых слов в день для изучения',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица назначений колод ученикам
CREATE TABLE deck_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deck_id INT NOT NULL,
    student_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_deck_student (deck_id, student_id)
);

-- Таблица словарей (с поддержкой изображений и аудио)
CREATE TABLE vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deck_id INT NOT NULL,
    foreign_word VARCHAR(255) NOT NULL,
    translation VARCHAR(255) NOT NULL,
    image_path VARCHAR(500) NULL,
    audio_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE
);

-- Таблица для отслеживания прогресса изучения
CREATE TABLE learning_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    vocabulary_id INT NOT NULL,
    ease_factor DECIMAL(3,2) DEFAULT 2.50,
    interval_days INT DEFAULT 1,
    repetition_count INT DEFAULT 0,
    total_attempts INT DEFAULT 0,
    next_review_date DATE NOT NULL,
    last_review_date TIMESTAMP NULL,
    difficulty_rating ENUM('easy', 'hard') NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vocabulary_id) REFERENCES vocabulary(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_vocabulary (student_id, vocabulary_id)
);

-- Таблица для отслеживания дневных лимитов изучения
CREATE TABLE daily_study_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    deck_id INT NOT NULL,
    study_date DATE NOT NULL,
    words_studied INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_deck_date (student_id, deck_id, study_date)
);

-- ===== СИСТЕМА ТЕСТОВ =====

-- Таблица тестов
CREATE TABLE tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deck_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    questions_count INT DEFAULT 10,
    time_limit INT NULL COMMENT 'Ограничение времени в минутах',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE
);

-- Таблица вопросов теста
CREATE TABLE test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_answer CHAR(1) NOT NULL CHECK (correct_answer IN ('A', 'B', 'C', 'D')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
);

-- Таблица попыток прохождения тестов
CREATE TABLE test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    student_id INT NOT NULL,
    correct_answers INT NOT NULL DEFAULT 0,
    total_questions INT NOT NULL DEFAULT 0,
    score DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Процент правильных ответов',
    time_spent INT NULL COMMENT 'Время в секундах',
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Таблица ответов на вопросы
CREATE TABLE test_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer CHAR(1) NULL COMMENT 'A, B, C, D или NULL если не отвечено',
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE
);

-- ===== ИНДЕКСЫ ДЛЯ ПРОИЗВОДИТЕЛЬНОСТИ =====

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_verification_token ON users(verification_token);
CREATE INDEX idx_vocabulary_audio_path ON vocabulary(audio_path);
CREATE INDEX idx_learning_progress_next_review ON learning_progress(next_review_date);
CREATE INDEX idx_learning_progress_student ON learning_progress(student_id);
CREATE INDEX idx_daily_limits_date ON daily_study_limits(study_date);

-- ===== НАЧАЛЬНЫЕ ДАННЫЕ =====

-- Создание учетной записи преподавателя по умолчанию
INSERT INTO users (username, password, role, first_name, last_name, email, email_verified) 
VALUES ('teacher', '$2y$10$1.nt8VwVW19XBW3PZybuTu1vOYcjMXbCU.3A0PwuembjzmsOBILqy', 'teacher', 'Преподаватель', 'По умолчанию', 'teacher@example.com', TRUE);
-- Пароль: password

-- Информационные комментарии
SELECT 'База данных QuizCard успешно создана!' as status;
SELECT 'Включены функции: основные таблицы, email верификация, аудио поддержка, система тестов' as features;
SELECT 'Учетная запись преподавателя: teacher / password' as default_account;
