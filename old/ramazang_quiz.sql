-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Июл 13 2025 г., 13:06
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
(4, 7, 'English. 20 words', '', '#ff0095', '2025-07-08 05:36:03', 20);

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
(4, 4, 8, '2025-07-08 11:48:45');

-- --------------------------------------------------------

--
-- Структура таблицы `email_verification_logs`
--

CREATE TABLE `email_verification_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('sent','verified','expired','failed') DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `email_verification_logs`
--

INSERT INTO `email_verification_logs` (`id`, `user_id`, `email`, `token`, `sent_at`, `verified_at`, `ip_address`, `user_agent`, `status`) VALUES
(1, 15, 'aksak1988@gmail.com', '1cd92ac60e11e7cc11a2b53a8fb769e3b9a4627aad78a89babf0b9d509d4a36e', '2025-07-11 04:33:00', '2025-07-11 04:34:30', '37.32.73.131', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'verified'),
(2, 16, 'timsarkz@gmail.com', '7a9b5182d2d5cdb3fe6456a9d2cffed4380038a9ecaec52d235328d7777f28e4', '2025-07-11 04:43:38', '2025-07-11 04:44:01', '37.32.73.131', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'verified'),
(3, 7, 'sarsembai.timur@gmail.com', '6626f2e9f7c95b35a6b3ba84bc65da878e978b6b513f6ffbb0a9694a0acb8a24', '2025-07-11 04:44:37', NULL, '37.32.73.131', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'sent'),
(4, 7, 'sarsembai.timur@gmail.com', '7e42cca5a78eb887dd4d58fecd43cfec1b83b54070ec249ce888e4d151693bd4', '2025-07-11 05:13:20', '2025-07-11 05:14:13', '188.246.251.96', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', 'verified');

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
(116, 8, 2, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(117, 8, 3, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(118, 8, 18, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(119, 8, 19, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(120, 8, 20, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(121, 8, 21, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(122, 8, 22, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(123, 8, 23, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(124, 8, 24, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(125, 8, 25, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(126, 8, 26, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(127, 8, 27, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(128, 8, 28, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(129, 8, 29, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(130, 8, 30, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(131, 8, 31, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(132, 8, 32, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(133, 8, 33, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(134, 8, 34, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(135, 8, 35, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(136, 8, 36, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35'),
(137, 8, 37, 2.50, 1, 0, 0, '2025-07-08', NULL, NULL, '2025-07-08 17:05:35', '2025-07-08 17:05:35');

-- --------------------------------------------------------

--
-- Структура таблицы `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `deck_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `questions_count` int(11) DEFAULT 10,
  `time_limit` int(11) DEFAULT NULL COMMENT 'Время в минутах, NULL = без ограничений',
  `test_type` varchar(50) NOT NULL DEFAULT 'word_to_translation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `tests`
--

INSERT INTO `tests` (`id`, `deck_id`, `name`, `questions_count`, `time_limit`, `test_type`, `created_at`, `updated_at`) VALUES
(1, 4, 'Тест по колоде \"English. 20 words\"', 20, 5, 'word_to_translation', '2025-07-08 11:54:03', '2025-07-08 11:54:03'),
(4, 4, 'English. 20 words. Images > Words', 20, NULL, 'image_to_word', '2025-07-08 15:09:27', '2025-07-08 15:09:27'),
(8, 4, 'English. 20 words. Words > Images', 10, NULL, 'word_to_image', '2025-07-08 15:54:31', '2025-07-08 15:54:31'),
(9, 4, 'Тестовый тест', 10, NULL, 'word_to_translation', '2025-07-10 02:54:50', '2025-07-10 02:54:50');

-- --------------------------------------------------------

--
-- Структура таблицы `test_answers`
--

CREATE TABLE `test_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_answer` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `test_answers`
--

INSERT INTO `test_answers` (`id`, `attempt_id`, `question_id`, `selected_answer`, `is_correct`, `created_at`) VALUES
(70, 5, 21, 'D', 0, '2025-07-10 02:06:34'),
(71, 5, 22, 'A', 0, '2025-07-10 02:06:34'),
(72, 5, 23, 'A', 0, '2025-07-10 02:06:34'),
(73, 5, 24, 'D', 0, '2025-07-10 02:06:34'),
(74, 5, 25, 'B', 0, '2025-07-10 02:06:34'),
(75, 5, 26, 'C', 0, '2025-07-10 02:06:34'),
(76, 5, 27, 'A', 0, '2025-07-10 02:06:34'),
(77, 5, 28, 'A', 0, '2025-07-10 02:06:34'),
(78, 5, 29, 'A', 0, '2025-07-10 02:06:34'),
(79, 5, 30, 'C', 0, '2025-07-10 02:06:34'),
(80, 5, 31, 'B', 0, '2025-07-10 02:06:34'),
(81, 5, 32, 'D', 0, '2025-07-10 02:06:34'),
(82, 5, 33, 'D', 0, '2025-07-10 02:06:34'),
(83, 5, 34, 'A', 0, '2025-07-10 02:06:34'),
(84, 5, 35, 'A', 0, '2025-07-10 02:06:34'),
(85, 5, 36, 'B', 0, '2025-07-10 02:06:34'),
(86, 5, 37, 'B', 0, '2025-07-10 02:06:34'),
(87, 5, 38, 'D', 0, '2025-07-10 02:06:34'),
(88, 5, 39, 'D', 0, '2025-07-10 02:06:34'),
(89, 5, 40, 'D', 0, '2025-07-10 02:06:34');

-- --------------------------------------------------------

--
-- Структура таблицы `test_attempts`
--

CREATE TABLE `test_attempts` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) NOT NULL DEFAULT 0 COMMENT 'Оценка в процентах (0-100)',
  `correct_answers` int(11) NOT NULL DEFAULT 0,
  `total_questions` int(11) NOT NULL DEFAULT 0,
  `time_spent` int(11) NOT NULL DEFAULT 0 COMMENT 'Время в секундах',
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `test_attempts`
--

INSERT INTO `test_attempts` (`id`, `test_id`, `student_id`, `score`, `correct_answers`, `total_questions`, `time_spent`, `completed_at`) VALUES
(5, 1, 8, 100, 20, 20, 81, '2025-07-10 02:06:34');

-- --------------------------------------------------------

--
-- Структура таблицы `test_questions`
--

CREATE TABLE `test_questions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `option_a` varchar(500) NOT NULL,
  `option_b` varchar(500) NOT NULL,
  `option_c` varchar(500) NOT NULL,
  `option_d` varchar(500) NOT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `test_questions`
--

INSERT INTO `test_questions` (`id`, `test_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `image_url`, `created_at`) VALUES
(21, 1, 'Как переводится слово: flower?', 'компьютер', 'стул', 'луна', 'цветок', 'D', NULL, '2025-07-08 11:55:08'),
(22, 1, 'Как переводится слово: water?', 'вода', 'телефон', 'кот', 'яблоко', 'A', NULL, '2025-07-08 11:55:08'),
(23, 1, 'Как переводится слово: sun?', 'солнце', 'вода', 'книга', 'луна', 'A', NULL, '2025-07-08 11:55:08'),
(24, 1, 'Как переводится слово: chair?', 'яблоко', 'кот', 'солнце', 'стул', 'D', NULL, '2025-07-08 11:55:08'),
(25, 1, 'Как переводится слово: book?', 'кот', 'книга', 'вода', 'ручка', 'B', NULL, '2025-07-08 11:55:08'),
(26, 1, 'Как переводится слово: bread?', 'дверь', 'яблоко', 'хлеб', 'дерево', 'C', NULL, '2025-07-08 11:55:08'),
(27, 1, 'Как переводится слово: window?', 'окно', 'яблоко', 'стул', 'собака', 'A', NULL, '2025-07-08 11:55:08'),
(28, 1, 'Как переводится слово: pen?', 'ручка', 'хлеб', 'дом', 'компьютер', 'A', NULL, '2025-07-08 11:55:08'),
(29, 1, 'Как переводится слово: phone?', 'телефон', 'молоко', 'собака', 'вода', 'A', NULL, '2025-07-08 11:55:08'),
(30, 1, 'Как переводится слово: door?', 'машина', 'цветок', 'дверь', 'телефон', 'C', NULL, '2025-07-08 11:55:08'),
(31, 1, 'Как переводится слово: computer?', 'машина', 'компьютер', 'кот', 'стол', 'B', NULL, '2025-07-08 11:55:08'),
(32, 1, 'Как переводится слово: apple?', 'дом', 'телефон', 'хлеб', 'яблоко', 'D', NULL, '2025-07-08 11:55:08'),
(33, 1, 'Как переводится слово: milk?', 'стол', 'телефон', 'машина', 'молоко', 'D', NULL, '2025-07-08 11:55:08'),
(34, 1, 'Как переводится слово: moon?', 'луна', 'яблоко', 'машина', 'хлеб', 'A', NULL, '2025-07-08 11:55:08'),
(35, 1, 'Как переводится слово: dog?', 'собака', 'цветок', 'телефон', 'молоко', 'A', NULL, '2025-07-08 11:55:08'),
(36, 1, 'Как переводится слово: table?', 'солнце', 'стол', 'кот', 'цветок', 'B', NULL, '2025-07-08 11:55:08'),
(37, 1, 'Как переводится слово: car?', 'телефон', 'машина', 'вода', 'окно', 'B', NULL, '2025-07-08 11:55:08'),
(38, 1, 'Как переводится слово: tree?', 'окно', 'машина', 'стол', 'дерево', 'D', NULL, '2025-07-08 11:55:08'),
(39, 1, 'Как переводится слово: cat?', 'компьютер', 'дверь', 'окно', 'кот', 'D', NULL, '2025-07-08 11:55:08'),
(40, 1, 'Как переводится слово: house?', 'молоко', 'дерево', 'луна', 'дом', 'D', NULL, '2025-07-08 11:55:08'),
(81, 4, 'Что изображено на картинке?', 'moon', 'cat', 'milk', 'tree', 'C', 'uploads/vocabulary/import_1751953000_milk.jpg', '2025-07-08 15:09:31'),
(82, 4, 'Что изображено на картинке?', 'apple', 'car', 'door', 'book', 'B', 'uploads/vocabulary/import_1751952992_car.jpg', '2025-07-08 15:09:31'),
(83, 4, 'Что изображено на картинке?', 'bread', 'tree', 'computer', 'cat', 'C', 'uploads/vocabulary/import_1751952999_computer.jpg', '2025-07-08 15:09:31'),
(84, 4, 'Что изображено на картинке?', 'pen', 'cat', 'window', 'tree', 'C', 'uploads/vocabulary/import_1751952997_window.jpg', '2025-07-08 15:09:31'),
(85, 4, 'Что изображено на картинке?', 'bread', 'cat', 'window', 'milk', 'A', 'uploads/vocabulary/import_1751953000_bread.jpg', '2025-07-08 15:09:31'),
(86, 4, 'Что изображено на картинке?', 'table', 'window', 'moon', 'book', 'D', 'uploads/vocabulary/import_1751952993_book.jpg', '2025-07-08 15:09:31'),
(87, 4, 'Что изображено на картинке?', 'pen', 'milk', 'dog', 'house', 'D', 'uploads/vocabulary/import_1751952992_house.jpg', '2025-07-08 15:09:31'),
(88, 4, 'Что изображено на картинке?', 'chair', 'apple', 'cat', 'dog', 'D', 'uploads/vocabulary/import_1751952995_dog.jpg', '2025-07-08 15:09:31'),
(89, 4, 'Что изображено на картинке?', 'dog', 'bread', 'phone', 'pen', 'C', 'uploads/vocabulary/import_1751952998_phone.jpg', '2025-07-08 15:09:31'),
(90, 4, 'Что изображено на картинке?', 'door', 'phone', 'chair', 'sun', 'C', 'uploads/vocabulary/import_1751952997_chair.jpg', '2025-07-08 15:09:31'),
(91, 4, 'Что изображено на картинке?', 'flower', 'door', 'pen', 'book', 'A', 'uploads/vocabulary/import_1751952996_flower.jpg', '2025-07-08 15:09:31'),
(92, 4, 'Что изображено на картинке?', 'house', 'bread', 'chair', 'pen', 'D', 'uploads/vocabulary/import_1751952999_pen.jpg', '2025-07-08 15:09:31'),
(93, 4, 'Что изображено на картинке?', 'table', 'sun', 'window', 'chair', 'B', 'uploads/vocabulary/import_1751952995_sun.jpg', '2025-07-08 15:09:31'),
(94, 4, 'Что изображено на картинке?', 'door', 'water', 'cat', 'table', 'B', 'uploads/vocabulary/import_1751952993_water.jpg', '2025-07-08 15:09:31'),
(95, 4, 'Что изображено на картинке?', 'tree', 'milk', 'moon', 'apple', 'C', 'uploads/vocabulary/import_1751952996_moon.jpg', '2025-07-08 15:09:31'),
(96, 4, 'Что изображено на картинке?', 'chair', 'flower', 'door', 'book', 'C', 'uploads/vocabulary/import_1751952998_door.jpg', '2025-07-08 15:09:31'),
(97, 4, 'Что изображено на картинке?', 'bread', 'car', 'window', 'cat', 'D', 'uploads/vocabulary/import_1751952994_cat.jpg', '2025-07-08 15:09:31'),
(98, 4, 'Что изображено на картинке?', 'bread', 'phone', 'milk', 'table', 'D', 'uploads/vocabulary/import_1751952997_table.jpg', '2025-07-08 15:09:31'),
(99, 4, 'Что изображено на картинке?', 'tree', 'apple', 'dog', 'sun', 'A', 'uploads/vocabulary/import_1751952994_tree.jpg', '2025-07-08 15:09:31'),
(100, 4, 'Что изображено на картинке?', 'sun', 'car', 'apple', 'cat', 'C', 'uploads/vocabulary/import_1751952991_apple.jpg', '2025-07-08 15:09:31'),
(171, 8, 'Какое изображение соответствует слову \'window\'?', 'uploads/vocabulary/import_1751952992_car.jpg', 'uploads/vocabulary/import_1751952993_water.jpg', 'uploads/vocabulary/import_1751952999_pen.jpg', 'uploads/vocabulary/import_1751952997_window.jpg', 'D', NULL, '2025-07-08 15:54:33'),
(172, 8, 'Какое изображение соответствует слову \'phone\'?', 'uploads/vocabulary/import_1751952998_phone.jpg', 'uploads/vocabulary/import_1751952999_pen.jpg', 'uploads/vocabulary/import_1751952996_flower.jpg', 'uploads/vocabulary/import_1751953000_milk.jpg', 'A', NULL, '2025-07-08 15:54:33'),
(173, 8, 'Какое изображение соответствует слову \'moon\'?', 'uploads/vocabulary/import_1751952995_dog.jpg', 'uploads/vocabulary/import_1751952993_water.jpg', 'uploads/vocabulary/import_1751952996_moon.jpg', 'uploads/vocabulary/import_1751952992_car.jpg', 'C', NULL, '2025-07-08 15:54:33'),
(174, 8, 'Какое изображение соответствует слову \'computer\'?', 'uploads/vocabulary/import_1751952996_moon.jpg', 'uploads/vocabulary/import_1751952998_phone.jpg', 'uploads/vocabulary/import_1751952992_car.jpg', 'uploads/vocabulary/import_1751952999_computer.jpg', 'D', NULL, '2025-07-08 15:54:33'),
(175, 8, 'Какое изображение соответствует слову \'bread\'?', 'uploads/vocabulary/import_1751952993_water.jpg', 'uploads/vocabulary/import_1751953000_bread.jpg', 'uploads/vocabulary/import_1751952995_sun.jpg', 'uploads/vocabulary/import_1751952999_pen.jpg', 'B', NULL, '2025-07-08 15:54:33'),
(176, 8, 'Какое изображение соответствует слову \'tree\'?', 'uploads/vocabulary/import_1751952999_computer.jpg', 'uploads/vocabulary/import_1751952997_window.jpg', 'uploads/vocabulary/import_1751952992_car.jpg', 'uploads/vocabulary/import_1751952994_tree.jpg', 'D', NULL, '2025-07-08 15:54:33'),
(177, 8, 'Какое изображение соответствует слову \'pen\'?', 'uploads/vocabulary/import_1751952994_cat.jpg', 'uploads/vocabulary/import_1751952996_moon.jpg', 'uploads/vocabulary/import_1751953000_bread.jpg', 'uploads/vocabulary/import_1751952999_pen.jpg', 'D', NULL, '2025-07-08 15:54:33'),
(178, 8, 'Какое изображение соответствует слову \'flower\'?', 'uploads/vocabulary/import_1751952997_chair.jpg', 'uploads/vocabulary/import_1751952996_flower.jpg', 'uploads/vocabulary/import_1751952996_moon.jpg', 'uploads/vocabulary/import_1751952993_water.jpg', 'B', NULL, '2025-07-08 15:54:33'),
(179, 8, 'Какое изображение соответствует слову \'door\'?', 'uploads/vocabulary/import_1751952999_computer.jpg', 'uploads/vocabulary/import_1751952998_door.jpg', 'uploads/vocabulary/import_1751952997_chair.jpg', 'uploads/vocabulary/import_1751952994_cat.jpg', 'B', NULL, '2025-07-08 15:54:33'),
(180, 8, 'Какое изображение соответствует слову \'house\'?', 'uploads/vocabulary/import_1751952997_table.jpg', 'uploads/vocabulary/import_1751952992_house.jpg', 'uploads/vocabulary/import_1751953000_milk.jpg', 'uploads/vocabulary/import_1751952999_pen.jpg', 'B', NULL, '2025-07-08 15:54:33');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_token_expires` datetime DEFAULT NULL,
  `last_verification_sent` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `first_name`, `last_name`, `email`, `teacher_id`, `created_at`, `email_verified`, `verification_token`, `verification_token_expires`, `last_verification_sent`) VALUES
(7, 'timur', '$2y$12$4947P44bHyY3hPpYqw7ad.xjZnLAZ2RnPMQrnffKsgyW2a.Vi2XEu', 'teacher', 'Тимур', 'Сарсембаев', 'sarsembai.timur@gmail.com', NULL, '2025-07-07 22:10:10', 1, NULL, NULL, '2025-07-11 05:13:19'),
(8, 'safia', '$2y$12$a/FO.xENg9lyRGPOykhS/OtzxSkKaY9qN8dKFXlHgr8K8gf9hzJom', 'student', 'Сафия', 'Сарсембай', NULL, 7, '2025-07-07 22:10:10', 0, NULL, NULL, NULL),
(10, 'beibarys', '$2y$10$H8VSDEdRqJBLmUUBsppOg.HALEr3AX.VTW5k2/o/iULn48WLI7HMS', 'student', 'Бейбарыс', 'Сарсембай', NULL, 7, '2025-07-08 12:33:12', 0, NULL, NULL, NULL),
(11, 'seifulmalik', '$2y$10$gUrPxsHkwsQBtjlwd5rKuOCKhIlkxdEgvWTA2ywCMUqrXghVlpHEi', 'student', 'Сейфульмалик', 'Сарсембай', NULL, 7, '2025-07-08 12:33:31', 0, NULL, NULL, NULL),
(12, 'dinara', '$2y$10$RwLYcq8UaN4eQclNq8EIk.RvVP1gBOWtULULiFld5i.Dnz6mDEiCK', 'teacher', 'Динара', 'Адилова', 'adilova.dinara.86@gmail.com', NULL, '2025-07-08 18:52:45', 0, NULL, NULL, NULL),
(13, 'mansur', '$2y$10$p9UG0xMsiglHntg.gAUV0.D/NfQI0ox5XfKuJaVK2ZbCC2AJptXWG', 'student', 'Мансур', 'Сарсембай', NULL, 12, '2025-07-08 18:58:13', 0, NULL, NULL, NULL),
(14, 'raushan', '$2y$10$Cev.kf2XEGFXxWgiGqNLOORsLxQllGu5ga2ArfSomuBKkurm/PjcW', 'teacher', 'Раушан', 'Сарсембаева', 'timursarsembayev@gmail.com', NULL, '2025-07-10 23:28:22', 0, 'd0fbd1f4bdc3257901f9091673cceb21f33cad47809b154dcae32fcfd179c5a1', '2025-07-12 04:28:22', '2025-07-11 04:28:22'),
(15, 'seypil', '$2y$10$KJV1gIUU0KqW6MrNZ3rhD./P1HJpiszu/8gMyvvXBEtA01AhqzBse', 'teacher', 'Сейпил', 'Сарсембаев', 'aksak1988@gmail.com', NULL, '2025-07-10 23:32:59', 1, NULL, NULL, '2025-07-11 04:32:59'),
(16, 'timsarkz', '$2y$10$GjNLfNBDgrwd8vOvY3UnZeSerlUcF1BNuBKRLNXPJIcpZARXXsrXC', 'teacher', 'Timsar', 'Timsarovich', 'timsarkz@gmail.com', NULL, '2025-07-10 23:43:37', 1, NULL, NULL, '2025-07-11 04:43:37');

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
  `audio_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `vocabulary`
--

INSERT INTO `vocabulary` (`id`, `deck_id`, `foreign_word`, `translation`, `image_path`, `audio_path`, `created_at`) VALUES
(2, 2, 'كِتَابٌ', 'Книга', 'uploads/686c53992a3f4.jpg', NULL, '2025-07-07 23:09:13'),
(3, 2, 'بَيْتٌ', 'Дом', 'uploads/686c53b09e632.jpg', 'uploads/audio/audio_1752143139_686f952390407.ogg', '2025-07-07 23:09:36'),
(18, 4, 'apple', 'яблоко', 'uploads/vocabulary/import_1751952991_apple.jpg', NULL, '2025-07-08 05:36:32'),
(19, 4, 'house', 'дом', 'uploads/vocabulary/import_1751952992_house.jpg', NULL, '2025-07-08 05:36:32'),
(20, 4, 'car', 'машина', 'uploads/vocabulary/import_1751952992_car.jpg', NULL, '2025-07-08 05:36:33'),
(21, 4, 'book', 'книга', 'uploads/vocabulary/import_1751952993_book.jpg', NULL, '2025-07-08 05:36:33'),
(22, 4, 'water', 'вода', 'uploads/vocabulary/import_1751952993_water.jpg', NULL, '2025-07-08 05:36:34'),
(23, 4, 'tree', 'дерево', 'uploads/vocabulary/import_1751952994_tree.jpg', NULL, '2025-07-08 05:36:34'),
(24, 4, 'cat', 'кот', 'uploads/vocabulary/import_1751952994_cat.jpg', NULL, '2025-07-08 05:36:35'),
(25, 4, 'dog', 'собака', 'uploads/vocabulary/import_1751952995_dog.jpg', NULL, '2025-07-08 05:36:35'),
(26, 4, 'sun', 'солнце', 'uploads/vocabulary/import_1751952995_sun.jpg', NULL, '2025-07-08 05:36:36'),
(27, 4, 'moon', 'луна', 'uploads/vocabulary/import_1751952996_moon.jpg', NULL, '2025-07-08 05:36:36'),
(28, 4, 'flower', 'цветок', 'uploads/vocabulary/import_1751952996_flower.jpg', NULL, '2025-07-08 05:36:37'),
(29, 4, 'chair', 'стул', 'uploads/vocabulary/import_1751952997_chair.jpg', NULL, '2025-07-08 05:36:37'),
(30, 4, 'table', 'стол', 'uploads/vocabulary/import_1751952997_table.jpg', NULL, '2025-07-08 05:36:37'),
(31, 4, 'window', 'окно', 'uploads/vocabulary/import_1751952997_window.jpg', NULL, '2025-07-08 05:36:38'),
(32, 4, 'door', 'дверь', 'uploads/vocabulary/import_1751952998_door.jpg', NULL, '2025-07-08 05:36:38'),
(33, 4, 'phone', 'телефон', 'uploads/vocabulary/import_1751952998_phone.jpg', NULL, '2025-07-08 05:36:39'),
(34, 4, 'computer', 'компьютер', 'uploads/vocabulary/import_1751952999_computer.jpg', NULL, '2025-07-08 05:36:39'),
(35, 4, 'pen', 'ручка', 'uploads/vocabulary/import_1751952999_pen.jpg', NULL, '2025-07-08 05:36:40'),
(36, 4, 'bread', 'хлеб', 'uploads/vocabulary/import_1751953000_bread.jpg', NULL, '2025-07-08 05:36:40'),
(37, 4, 'milk', 'молоко', 'uploads/vocabulary/import_1751953000_milk.jpg', NULL, '2025-07-08 05:36:41');

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
-- Индексы таблицы `email_verification_logs`
--
ALTER TABLE `email_verification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_status` (`status`);

--
-- Индексы таблицы `learning_progress`
--
ALTER TABLE `learning_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_vocabulary` (`student_id`,`vocabulary_id`),
  ADD KEY `vocabulary_id` (`vocabulary_id`);

--
-- Индексы таблицы `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deck_id` (`deck_id`);

--
-- Индексы таблицы `test_answers`
--
ALTER TABLE `test_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `idx_attempt_id` (`attempt_id`),
  ADD KEY `idx_question_id` (`question_id`);

--
-- Индексы таблицы `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_student` (`test_id`,`student_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_completed_at` (`completed_at`);

--
-- Индексы таблицы `test_questions`
--
ALTER TABLE `test_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_id` (`test_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `idx_verification_token` (`verification_token`),
  ADD KEY `idx_email_verified` (`email_verified`);

--
-- Индексы таблицы `vocabulary`
--
ALTER TABLE `vocabulary`
  ADD PRIMARY KEY (`id`),
  ADD KEY `deck_id` (`deck_id`),
  ADD KEY `idx_vocabulary_audio_path` (`audio_path`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `deck_assignments`
--
ALTER TABLE `deck_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `email_verification_logs`
--
ALTER TABLE `email_verification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `learning_progress`
--
ALTER TABLE `learning_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT для таблицы `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `test_answers`
--
ALTER TABLE `test_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT для таблицы `test_attempts`
--
ALTER TABLE `test_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `test_questions`
--
ALTER TABLE `test_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `vocabulary`
--
ALTER TABLE `vocabulary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

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
-- Ограничения внешнего ключа таблицы `email_verification_logs`
--
ALTER TABLE `email_verification_logs`
  ADD CONSTRAINT `email_verification_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `learning_progress`
--
ALTER TABLE `learning_progress`
  ADD CONSTRAINT `learning_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_progress_ibfk_2` FOREIGN KEY (`vocabulary_id`) REFERENCES `vocabulary` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`deck_id`) REFERENCES `decks` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `test_answers`
--
ALTER TABLE `test_answers`
  ADD CONSTRAINT `test_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `test_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `test_questions` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `test_attempts`
--
ALTER TABLE `test_attempts`
  ADD CONSTRAINT `test_attempts_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `test_questions`
--
ALTER TABLE `test_questions`
  ADD CONSTRAINT `test_questions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

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
