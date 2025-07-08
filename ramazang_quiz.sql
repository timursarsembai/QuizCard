-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Июл 08 2025 г., 08:44
-- Версия сервера: 10.6.22-MariaDB
-- Версия PHP: 8.4.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `ramazang_quiz`
--

-- --------------------------------------------------------

--
-- Структура таблицы `daily_study_limits`
--

CREATE TABLE `daily_study_limits` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `deck_id` int(11) NOT NULL,
  `study_date` date NOT NULL,
  `words_studied` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `decks`
--

CREATE TABLE `decks` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#667eea',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `daily_word_limit` int(11) DEFAULT 20 COMMENT 'Лимит новых слов в день для изучения'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `decks`
--

INSERT INTO `decks` (`id`, `teacher_id`, `name`, `description`, `color`, `created_at`, `daily_word_limit`) VALUES
(2, 7, 'Мединский курс. Том 1. Урок 1. Часть 1', '', '#00fa60', '2025-07-07 22:45:34', 20),
(3, 7, 'Английский. Тест', '', '#ff00bb', '2025-07-07 22:46:41', 10);

-- --------------------------------------------------------

--
-- Структура таблицы `deck_assignments`
--

CREATE TABLE `deck_assignments` (
  `id` int(11) NOT NULL,
  `deck_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `deck_assignments`
--

INSERT INTO `deck_assignments` (`id`, `deck_id`, `student_id`, `assigned_at`) VALUES
(2, 2, 8, '2025-07-07 22:48:09'),
(3, 3, 8, '2025-07-08 01:16:17');

-- --------------------------------------------------------

--
-- Структура таблицы `learning_progress`
--

CREATE TABLE `learning_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `vocabulary_id` int(11) NOT NULL,
  `ease_factor` decimal(3,2) DEFAULT 2.50,
  `interval_days` int(11) DEFAULT 1,
  `repetition_count` int(11) DEFAULT 0,
  `total_attempts` int(11) DEFAULT 0,
  `next_review_date` date NOT NULL,
  `last_review_date` timestamp NULL DEFAULT NULL,
  `difficulty_rating` enum('easy','hard') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `learning_progress`
--

INSERT INTO `learning_progress` (`id`, `student_id`, `vocabulary_id`, `ease_factor`, `interval_days`, `repetition_count`, `total_attempts`, `next_review_date`, `last_review_date`, `difficulty_rating`, `created_at`, `updated_at`) VALUES
(3, 8, 2, 2.30, 1, 0, 1, '2025-07-09', '2025-07-08 01:37:25', 'hard', '2025-07-07 23:09:13', '2025-07-08 02:48:29'),
(4, 8, 3, 2.50, 1, 1, 1, '2025-07-09', '2025-07-08 01:37:24', 'easy', '2025-07-07 23:09:36', '2025-07-08 02:48:29'),
(5, 8, 8, 2.50, 1, 1, 1, '2025-07-09', '2025-07-08 01:26:02', 'easy', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(6, 8, 9, 2.50, 1, 1, 1, '2025-07-09', '2025-07-08 01:26:04', 'easy', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(7, 8, 10, 2.50, 1, 1, 1, '2025-07-09', '2025-07-08 01:25:57', 'easy', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(8, 8, 11, 2.30, 1, 0, 1, '2025-07-09', '2025-07-08 01:26:03', 'hard', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(9, 8, 12, 2.50, 1, 1, 1, '2025-07-09', '2025-07-08 01:25:55', 'easy', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(10, 8, 13, 2.30, 1, 0, 1, '2025-07-09', '2025-07-08 01:26:00', 'hard', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(11, 8, 14, 2.30, 1, 0, 1, '2025-07-09', '2025-07-08 01:25:58', 'hard', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(12, 8, 15, 2.30, 1, 0, 1, '2025-07-09', '2025-07-08 01:26:04', 'hard', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(13, 8, 16, 2.30, 1, 0, 1, '2025-07-09', '2025-07-08 01:25:59', 'hard', '2025-07-08 01:16:17', '2025-07-08 02:48:29'),
(14, 8, 17, 2.50, 1, 1, 1, '2025-07-09', '2025-07-08 01:26:01', 'easy', '2025-07-08 01:16:17', '2025-07-08 02:48:29');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('teacher','student') NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `first_name`, `last_name`, `email`, `teacher_id`, `created_at`) VALUES
(7, 'teacher', '$2y$10$bo6IkvjRRNuA9Fc6YiRIoO3TvhkzryN7OFxBc5kCM.cEfk0cFx3f6', 'teacher', 'Тестовый', 'Преподаватель', 'teacher@example.com', NULL, '2025-07-07 22:10:10'),
(8, 'safia', '$2y$10$axypMrkPWEhxMxBqiuJlVeTAo6XCw75XIiGE8ILlmH6R2gX//O7HG', 'student', 'Сафия', 'Сарсембай', NULL, 7, '2025-07-07 22:10:10');

-- --------------------------------------------------------

--
-- Структура таблицы `vocabulary`
--

CREATE TABLE `vocabulary` (
  `id` int(11) NOT NULL,
  `deck_id` int(11) NOT NULL,
  `foreign_word` varchar(255) NOT NULL,
  `translation` varchar(255) NOT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `vocabulary`
--

INSERT INTO `vocabulary` (`id`, `deck_id`, `foreign_word`, `translation`, `image_path`, `created_at`) VALUES
(2, 2, 'كِتَابٌ', 'Книга', 'uploads/686c53992a3f4.jpg', '2025-07-07 23:09:13'),
(3, 2, 'بَيْتٌ', 'Дом', 'uploads/686c53b09e632.jpg', '2025-07-07 23:09:36'),
(8, 3, 'apple', 'яблоко', 'uploads/vocabulary/import_1751937322_apple.jpg', '2025-07-08 01:15:23'),
(9, 3, 'house', 'дом', 'uploads/vocabulary/import_1751937323_house.jpg', '2025-07-08 01:15:23'),
(10, 3, 'car', 'машина', 'uploads/vocabulary/import_1751937323_car.jpg', '2025-07-08 01:15:24'),
(11, 3, 'book', 'книга', 'uploads/vocabulary/import_1751937324_book.jpg', '2025-07-08 01:15:24'),
(12, 3, 'water', 'вода', 'uploads/vocabulary/vocab_1751945939_686c92d321954.jpg', '2025-07-08 01:15:24'),
(13, 3, 'tree', 'дерево', 'uploads/vocabulary/import_1751937324_tree.jpg', '2025-07-08 01:15:25'),
(14, 3, 'cat', 'кот', 'uploads/vocabulary/import_1751937325_cat.jpg', '2025-07-08 01:15:25'),
(15, 3, 'dog', 'собака', 'uploads/vocabulary/import_1751937325_dog.jpg', '2025-07-08 01:15:26'),
(16, 3, 'sun', 'солнце', 'uploads/vocabulary/import_1751937326_sun.jpg', '2025-07-08 01:15:26'),
(17, 3, 'moon', 'луна', 'uploads/vocabulary/import_1751937326_moon.jpg', '2025-07-08 01:15:27');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `daily_study_limits`
--
ALTER TABLE `daily_study_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_deck_date` (`student_id`,`deck_id`,`study_date`),
  ADD KEY `deck_id` (`deck_id`),
  ADD KEY `idx_daily_study_limits_date` (`study_date`),
  ADD KEY `idx_daily_study_limits_student_date` (`student_id`,`study_date`);

--
-- Индексы таблицы `decks`
--
ALTER TABLE `decks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Индексы таблицы `deck_assignments`
--
ALTER TABLE `deck_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_deck_student` (`deck_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Индексы таблицы `learning_progress`
--
ALTER TABLE `learning_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_vocabulary` (`student_id`,`vocabulary_id`),
  ADD KEY `vocabulary_id` (`vocabulary_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Индексы таблицы `vocabulary`
--
ALTER TABLE `vocabulary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deck_id` (`deck_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `daily_study_limits`
--
ALTER TABLE `daily_study_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `decks`
--
ALTER TABLE `decks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `deck_assignments`
--
ALTER TABLE `deck_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `learning_progress`
--
ALTER TABLE `learning_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `vocabulary`
--
ALTER TABLE `vocabulary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `daily_study_limits`
--
ALTER TABLE `daily_study_limits`
  ADD CONSTRAINT `daily_study_limits_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `daily_study_limits_ibfk_2` FOREIGN KEY (`deck_id`) REFERENCES `decks` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `decks`
--
ALTER TABLE `decks`
  ADD CONSTRAINT `decks_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `deck_assignments`
--
ALTER TABLE `deck_assignments`
  ADD CONSTRAINT `deck_assignments_ibfk_1` FOREIGN KEY (`deck_id`) REFERENCES `decks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deck_assignments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `learning_progress`
--
ALTER TABLE `learning_progress`
  ADD CONSTRAINT `learning_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_progress_ibfk_2` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabulary` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `vocabulary`
--
ALTER TABLE `vocabulary`
  ADD CONSTRAINT `vocabulary_ibfk_1` FOREIGN KEY (`deck_id`) REFERENCES `decks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
