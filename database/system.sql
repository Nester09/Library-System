-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 05, 2026 at 03:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `system`
--

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `available_copies` int(11) DEFAULT 1,
  `total_copies` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `available_copies`, `total_copies`) VALUES
(3, 'The Forbidden Kingdom', ' Jackie Chan', 900, 900),
(4, 'Saving Private Ryan(1998)', 'Master Mind', 35, 35),
(5, 'Rush Hour', 'Jackie Chan', 700, 700),
(6, 'Armour Of GOD', 'Jackie Chan', 999, 999),
(8, 'The Eleventh Hour(2008)', ' Navy Seal🍷🍔', 563, 563),
(9, 'The Bridge at Remagen(1969)', 'Western Front', 10, 10),
(10, 'Seal Team Six: The Raid On The Terminal List(2022)', 'Navy Seal🍷🍔', 5, 5),
(11, 'The Tuxedo', 'Jackie Chan', 74, 74),
(12, 'The Green', 'Master and Commander', 100, 100),
(13, 'The Great Escape', 'Master and Commander', 70, 70),
(16, 'The Medallion ', ' Jackie Chan', 63, 63),
(21, 'Black Hawk Down(2001)', 'Master Mind', 4, 4),
(22, 'The Wind That Shakes The Barley(2006)', 'Western Front', 768, 768),
(23, 'By God\'s Decree', 'Kapil Dev', 355, 355),
(24, 'A Million Mutinies Now', 'V.S. Naipaul', 6565, 6565),
(25, 'Facing Mount Kenya', ' Jomo Kenyatta', 699, 699),
(26, 'A Passage to England', 'Nirad C. Chaudhuri', 788, 788),
(27, 'South B’s Finest', 'Makena Maganjo', 699, 699),
(28, 'Hurling Words at Consciousness', 'Mukoma wa Ngũgĩ', 1000, 1000),
(29, 'The River Between', 'Ngũgĩ wa Thiong\'o', 700, 700),
(30, 'A River Sutra', 'Gita Mehta', 800, 800),
(32, 'One Day I Will Write About This Place', 'Binyavanga Wainaina', 200, 200),
(33, 'Chemmeen', 'Thakazhi Sivasankara Pillai', 500, 500),
(34, 'To Kill a Mockingbird', 'Harper Lee', 350, 350);

-- --------------------------------------------------------

--
-- Table structure for table `borrowed_books`
--

CREATE TABLE `borrowed_books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `book_id` int(11) DEFAULT NULL,
  `status` enum('pending','confirmed','denied') DEFAULT 'pending',
  `borrow_date` datetime DEFAULT current_timestamp(),
  `return_date` datetime DEFAULT NULL,
  `due_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_settings`
--

CREATE TABLE `library_settings` (
  `id` int(11) NOT NULL,
  `welcome_message` text DEFAULT NULL,
  `privacy_policy` text DEFAULT NULL,
  `library_history` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `library_settings`
--

INSERT INTO `library_settings` (`id`, `welcome_message`, `privacy_policy`, `library_history`) VALUES
(1, 'Discover a world of knowledge at your fingertips. Our library offers a vast collection of books, resources, and services designed to inspire and support your learning journey. Join us in exploring new ideas and expanding your horizons!', 'At our library, your privacy is our priority. We are committed to protecting your personal information and ensuring that your interactions with us are confidential. We collect only the necessary information to provide you with our services and will never share your data without your consent, unless required by law. Your reading history, account details, and any personal information are securely stored and accessible only by authorized personnel.', 'Established in 2023, our library has been a cornerstone of the community, dedicated to fostering a love for reading and learning. From humble beginnings, we have grown into a vibrant hub for knowledge seekers of all ages. Our commitment to providing access to diverse resources continues to drive us forward as we adapt to the needs of our patrons.');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`id`, `user_id`, `message`, `sent_at`, `is_deleted`) VALUES
(49, 10, 'Thank you for choosing our library as your gateway to knowledge. Happy exploring!', '2024-11-15 22:15:00', 0),
(50, 2, 'Thank you for choosing our library as your gateway to knowledge. Happy exploring!', '2024-11-15 22:15:00', 0),
(53, 4, 'Thank you for choosing our library as your gateway to knowledge. Happy exploring!', '2024-11-15 22:15:00', 0),
(54, 12, 'Thank you for choosing our library as your gateway to knowledge. Happy exploring!', '2024-11-15 22:15:00', 0),
(62, 2, 'Check Out Our New Arrivals! :Hello User, We’re excited to announce that new titles have just arrived at our Online library system! Visit us today to borrow these exciting new additions or check them out online! Happy reading! Online Library Team', '2024-11-16 18:11:41', 1),
(276, 10, 'Check Out Our New Arrivals! :Hello User, We’re excited to announce that new titles have just arrived at our Online library system! Visit us today to borrow these exciting new additions or check them out online! Happy reading! Online Library Team', '2025-05-16 10:37:07', 0),
(277, 2, 'Check Out Our New Arrivals! :Hello User, We’re excited to announce that new titles have just arrived at our Online library system! Visit us today to borrow these exciting new additions or check them out online! Happy reading! Online Library Team', '2025-05-16 10:37:07', 0),
(280, 4, 'Check Out Our New Arrivals! :Hello User, We’re excited to announce that new titles have just arrived at our Online library system! Visit us today to borrow these exciting new additions or check them out online! Happy reading! Online Library Team', '2025-05-16 10:37:07', 0),
(281, 12, 'Check Out Our New Arrivals! :Hello User, We’re excited to announce that new titles have just arrived at our Online library system! Visit us today to borrow these exciting new additions or check them out online! Happy reading! Online Library Team', '2025-05-16 10:37:07', 0);

-- --------------------------------------------------------

--
-- Table structure for table `returned_books`
--

CREATE TABLE `returned_books` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `return_date` datetime DEFAULT current_timestamp(),
  `borrow_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returned_books`
--

INSERT INTO `returned_books` (`id`, `user_id`, `book_id`, `return_date`, `borrow_date`) VALUES
(19, 12, 21, '2024-10-21 20:28:57', '2024-11-22 19:53:31'),
(24, 12, 4, '2024-10-30 11:59:45', '2024-11-22 19:53:31'),
(80, 2, 3, '2026-09-03 20:05:44', '2026-09-03 20:02:43'),
(81, 2, 3, '2026-09-03 20:06:06', '2026-09-03 20:02:38');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `review_text` text NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `response` text DEFAULT NULL,
  `response_created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `book_id`, `review_text`, `rating`, `created_at`, `response`, `response_created_at`) VALUES
(8, 2, 10, 'Good', 4, '2024-11-27 16:49:18', 'Thank you for your positive feedback! We\'re glad you enjoyed our services.', NULL),
(16, 2, 23, 'Good', 5, '2024-11-28 14:19:05', 'Thank you for your review! We value all feedback.', NULL),
(21, 2, 6, 'This is so cool.', 5, '2024-12-02 11:46:05', 'Thank you for your positive feedback! We\'re glad you enjoyed our services.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `login_attempts` int(11) DEFAULT 0,
  `lockout_time` datetime DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `login_attempts`, `lockout_time`, `reset_token`, `created_at`) VALUES
(2, 'LibraryUser1', 'libraryuser1@gmail.com', '$2y$10$t/Lwx0mtbZp.Ns34hYwJTe4bRWmvIXURqrcXH3xLE2tgdYJLNJZWa', 0, 0, NULL, 'ae9314755543167adea1fe04af3bb48d928e450e75af9454c935e091eec841f7', '2024-10-07 13:08:45'),
(4, 'LibraryAdmin1', 'libraryadmin1@gmail.com', '$2y$10$DANoj9rjUJ/WEtxvLAVRtu4uN7BWyXDoPeqK8jxOzAy3O9760Xojq', 1, 0, NULL, NULL, '2024-10-07 13:08:45'),
(10, 'LibraryUser2', 'libraryuser2@gmail.com', '$2y$10$x/pH6O1GO7dcDjpjSNZ8PuSU.YZ5FHVyOw1idtfldZ5ufR21c3b3y', 0, 0, NULL, NULL, '2024-10-07 13:08:45'),
(12, 'LibraryUser3', 'libraryuser3@gmail.com', '$2y$10$0gLtSYwkKs69S.kR3mT5hOi9cyzOi2eNBH79icmPsoK9L.EWsFHii', 0, 0, NULL, NULL, '2024-10-07 16:53:11'),
(43, 'LibraryAdmin2', 'libraryadmin2@gmail.com', '$2y$10$/tCmoPH2tdvs.P/2IaUgB.spQQTlTq/NF/ERxG4qpeW7A6NSCKtqC', 1, 0, NULL, NULL, '2026-09-05 13:15:49');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `library_settings`
--
ALTER TABLE `library_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `returned_books`
--
ALTER TABLE `returned_books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `library_settings`
--
ALTER TABLE `library_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=282;

--
-- AUTO_INCREMENT for table `returned_books`
--
ALTER TABLE `returned_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `borrowed_books`
--
ALTER TABLE `borrowed_books`
  ADD CONSTRAINT `borrowed_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `borrowed_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returned_books`
--
ALTER TABLE `returned_books`
  ADD CONSTRAINT `returned_books_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `returned_books_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
