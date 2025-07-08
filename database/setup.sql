-- Создание базы данных и таблиц для QuizCard

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

-- Таблица словарей (теперь привязана к колодам)
CREATE TABLE vocabulary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deck_id INT NOT NULL,
    foreign_word VARCHAR(255) NOT NULL,
    translation VARCHAR(255) NOT NULL,
    image_path VARCHAR(500) NULL,
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

-- Создание учетной записи преподавателя по умолчанию
INSERT INTO users (username, password, role, first_name, last_name, email) 
VALUES ('teacher', '$2y$10$1.nt8VwVW19XBW3PZybuTu1vOYcjMXbCU.3A0PwuembjzmsOBILqy', 'teacher', 'Преподаватель', 'По умолчанию', 'teacher@example.com');
-- Пароль: password
