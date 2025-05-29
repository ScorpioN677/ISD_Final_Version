-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 27, 2025 at 02:14 PM
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
-- Database: `pollify`
--

-- --------------------------------------------------------

--
-- Table structure for table `answers`
--

CREATE TABLE `answers` (
  `answer_id` int(11) NOT NULL,
  `text` varchar(255) NOT NULL,
  `vote_count` int(11) NOT NULL DEFAULT 0,
  `poll_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `answers`
--

INSERT INTO `answers` (`answer_id`, `text`, `vote_count`, `poll_id`) VALUES
(13, 'abdallah', 0, 9),
(14, 'jaafar', 0, 9),
(15, 'Sir', 0, 10),
(16, 'nigg', 0, 10),
(17, 'Sir', 0, 11),
(18, 'Sir', 0, 11),
(19, 'nigg', 0, 11),
(20, 'me', 0, 12),
(21, 'me', 0, 12),
(22, 'JavaScript', 12, 17),
(23, 'Python', 24, 17),
(24, 'Java', 8, 17),
(25, 'C#', 6, 17),
(26, 'PHP', 3, 17),
(27, 'Apple', 15, 16),
(28, 'Samsung', 18, 16),
(29, 'Google', 7, 16),
(30, 'Xiaomi', 10, 16),
(31, 'Porsche 718', 8, 15),
(32, 'Toyota Supra', 7, 15),
(33, 'Chevrolet Corvette', 10, 15),
(34, 'BMW M2', 5, 15),
(35, 'PlayStation 6', 22, 14),
(36, 'Xbox Series X Pro', 17, 14),
(37, 'Nintendo Switch 2', 15, 14),
(38, 'Baking', 8, 13),
(39, 'Grilling', 9, 13),
(40, 'Air Frying', 14, 13),
(41, 'Boiling/Steaming', 5, 13),
(42, 'Slow Cooking', 9, 13),
(43, 'Sir', 0, 18),
(44, '21212', 0, 18),
(45, 'Sir', 0, 19),
(46, '21212', 0, 19),
(47, 'Sir', 1, 20),
(48, 'nigg', 0, 20),
(49, 'h', 0, 21),
(50, '2', 0, 21),
(51, '32', 0, 22),
(52, '22', 0, 22),
(53, 'Sir', 0, 23),
(54, '21', 0, 23),
(55, 'www', 1, 24),
(56, 'w22', 0, 24),
(57, '212', 2, 25),
(58, '2222', 0, 25);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(50) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`) VALUES
(1, 'AI'),
(2, 'CARS'),
(3, 'GAMES'),
(4, 'CLOTHES'),
(5, 'SPORTS'),
(6, 'PROGRAMMING'),
(7, 'COOKING'),
(8, 'BOOKS'),
(9, 'COMPUTER PARTS');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `text` varchar(255) NOT NULL,
  `user_id` int(50) NOT NULL,
  `poll_id` int(50) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `text`, `user_id`, `poll_id`, `parent_comment_id`) VALUES
(1, 'hello', 21, 13, NULL),
(2, 'ok', 21, 13, NULL),
(3, 'ok', 24, 13, 1),
(4, 'hi', 24, 16, NULL),
(5, 'hello', 24, 25, NULL),
(6, 'hi', 24, 25, 5),
(7, 'hello', 19, 25, 5),
(8, 'hi', 19, 25, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `UserID` int(11) NOT NULL,
  `PollID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`UserID`, `PollID`) VALUES
(19, 25),
(21, 13),
(24, 14),
(24, 25),
(26, 13);

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `FollowerID` int(11) NOT NULL,
  `FollowingID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`FollowerID`, `FollowingID`) VALUES
(20, 22),
(21, 20),
(24, 20);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(50) NOT NULL,
  `type` varchar(50) NOT NULL,
  `related_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `related_id`, `message`, `is_read`, `created_at`) VALUES
(1, 20, 'follow', 21, 'Ali Abdallah is now following you', 0, '2025-05-17 18:30:31'),
(2, 20, 'follow', 21, 'Ali Abdallah is now following you', 0, '2025-05-17 18:30:33'),
(3, 20, 'follow', 21, 'Ali Abdallah is now following you', 0, '2025-05-17 18:30:37'),
(4, 20, 'vote', 2, 'Ali Abdal voted on your poll: \"What is your favorite programm...\"', 0, '2025-05-26 00:26:12'),
(5, 20, 'follow', 26, 'Ali Abdal is now following you', 0, '2025-05-26 00:26:13'),
(6, 21, 'follow', 26, 'Ali Abdal is now following you', 0, '2025-05-26 00:26:26'),
(7, 22, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 01:15:02'),
(8, 20, 'vote', 3, 'Ali Ali voted on your poll: \"What is your favorite programm...\"', 0, '2025-05-26 20:43:24'),
(9, 21, 'reply', 3, 'Ali Ali replied to your comment', 0, '2025-05-26 20:43:53'),
(10, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 21:26:13'),
(11, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 21:26:18'),
(12, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 21:26:19'),
(13, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 21:26:20'),
(14, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 21:26:20'),
(15, 20, 'comment', 4, 'Ali Ali commented on your poll: \"Which gaming console is better...\"', 0, '2025-05-26 21:26:29'),
(16, 26, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 22:27:50'),
(17, 21, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 22:55:27'),
(18, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-26 23:23:03'),
(19, 20, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-27 00:27:55'),
(20, 24, 'reply', 7, 'Ali Abed replied to your comment', 0, '2025-05-27 11:38:36'),
(21, 24, 'comment', 8, 'Ali Abed commented on your poll: \"alizzz\"', 0, '2025-05-27 11:38:47'),
(22, 22, 'follow', 24, 'Ali Ali is now following you', 0, '2025-05-27 14:10:37');

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `poll_id` int(50) NOT NULL,
  `question` varchar(255) NOT NULL,
  `date_of_creation` date NOT NULL,
  `isAnonymous` tinyint(1) NOT NULL,
  `isPublic` tinyint(1) NOT NULL,
  `CreatedBy` int(50) NOT NULL,
  `CategoryID` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`poll_id`, `question`, `date_of_creation`, `isAnonymous`, `isPublic`, `CreatedBy`, `CategoryID`) VALUES
(9, 'ali', '2025-04-24', 1, 1, 16, 1),
(10, 'hello', '2025-04-24', 0, 1, 16, 1),
(11, 'hello', '2025-04-29', 1, 1, 18, 9),
(12, 'hello', '2025-05-06', 0, 1, 21, 1),
(13, 'What is your favorite programming language?', '2025-05-14', 0, 1, 20, 6),
(14, 'Which smartphone brand do you prefer?', '2025-05-14', 0, 1, 20, 1),
(15, 'What is the best sports car under $100K?', '2025-05-13', 0, 1, 20, 2),
(16, 'Which gaming console is better in 2025?', '2025-05-12', 1, 1, 20, 3),
(17, 'What cooking method do you use most often?', '2025-05-10', 0, 0, 20, 7),
(18, 'hello', '2025-05-15', 1, 0, 19, 1),
(19, 'hello', '2025-05-26', 1, 0, 24, 1),
(20, 'mohamad', '2025-05-26', 0, 1, 24, 1),
(21, 'ali', '2025-05-26', 0, 1, 24, 8),
(22, 'hello', '2025-05-26', 0, 1, 24, 1),
(23, 'mh2', '2025-05-26', 0, 0, 24, 1),
(24, 'hello', '2025-05-26', 0, 1, 24, 5),
(25, 'alizzz', '2025-05-27', 0, 1, 24, 5);

-- --------------------------------------------------------

--
-- Table structure for table `profilepictures`
--

CREATE TABLE `profilepictures` (
  `picture_id` int(11) NOT NULL,
  `file` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL,
  `size` int(11) NOT NULL,
  `user_id` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profilepictures`
--

INSERT INTO `profilepictures` (`picture_id`, `file`, `type`, `size`, `user_id`) VALUES
(1, '6815ef64b50ed.png', 'image/png', 43430, 19),
(2, '68250ac54c541.png', 'image/png', 3979922, 20),
(3, '6819c9ce5b4fa.png', 'image/png', 204, 21),
(4, '6819ca504e71f.png', 'image/png', 204, 21),
(5, '6825a5b9d2555.png', 'image/png', 14368, 24);

-- --------------------------------------------------------

--
-- Table structure for table `responses`
--

CREATE TABLE `responses` (
  `response_id` int(11) NOT NULL,
  `poll_id` int(50) NOT NULL,
  `answer_id` int(11) NOT NULL,
  `user_id` int(50) NOT NULL,
  `response_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `responses`
--

INSERT INTO `responses` (`response_id`, `poll_id`, `answer_id`, `user_id`, `response_date`) VALUES
(1, 13, 41, 21, '2025-05-17 18:51:39'),
(2, 13, 38, 26, '2025-05-26 00:26:12'),
(3, 13, 42, 24, '2025-05-27 11:25:10'),
(4, 20, 47, 24, '2025-05-26 22:40:49'),
(54, 16, 30, 24, '2025-05-27 11:25:01'),
(55, 15, 32, 24, '2025-05-27 11:27:55'),
(56, 25, 57, 24, '2025-05-27 11:27:47'),
(57, 24, 55, 24, '2025-05-27 14:15:54'),
(58, 25, 57, 19, '2025-05-27 11:39:04');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(50) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` tinyint(1) NOT NULL,
  `address` varchar(200) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `bio` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `gender`, `address`, `phone_number`, `bio`) VALUES
(16, 'Deleted', 'User', 'deleted.user16@example.com', '$2y$10$defaulthashedpasswordplaceholder123456789', 0, '', '', 'User account was deleted but polls remain'),
(17, 'Ali', 'abdallah', 'test@test.com', '$2y$10$c2rVcX2BwztGRh2YsgO8W.vmLtcVFAzfBJs738vNJ..BaWRvY3i4W', 0, 'qsaibe', '', ''),
(18, 'Ali', 'Amro', 'test123@test.com', '$2y$10$j3yotjnLDPmk3a0/V0eXvO0ISedcKkWqI.NMXZh4rzOSm8gV.mgyO', 0, 'nab', '', ''),
(19, 'Ali', 'Abed', 'testing@test.com', '$2y$10$ORkoiUaMf2rCWcR17MEjyuflD4jfo5jQxD3yXeGsHVuhIis3Yam3e', 0, 'nab', '', ''),
(20, 'Mohamad', 'Abdallah', 'testing1232@g.com', '$2y$10$7LnHOeUWUT4MpeK3YGnF5eLCWPrKaNR6Fho0CjDhkR4YEBUk3lP12', 0, 'nab', '706667665', 'hello'),
(21, 'Ali', 'Abdallah', 'testing223@test.com', '$2y$10$JBtlkt.UDcyyQu8vF7w5oeRQVvRHi3Wz2BtBC1.445jPS66gZu/q.', 0, 'Arabsalim', '', 'asdasdads\'dfgfg\\0x42'),
(22, 'Abdallah', 'Ahmad', 'test@tesing.com', '$2y$10$gfzBraIqd0a6a9n77xcIi.1FGmQQQi/bH2qfE/zC.CPFx9jECIvei', 0, 'arabsalim', '', ''),
(24, 'Ali', 'Ali', 'tst@test.com', '$2y$10$Q5ZAPtizrmIf2XtXyq6NzuApuD.wZ.j4X2aIO1Rvq7x5Hl2rMew3q', 0, 'nab', '', ''),
(26, 'Ali', 'Abdal', 'ali_1@testing.com', '$2y$10$Fc9K6SCm4calPz0fMTRzWeNsVGwiSgp1nFQFF15NVdQyUFCBWhRZ6', 0, 'Arabsalim', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `answers`
--
ALTER TABLE `answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `poll_id` (`poll_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `poll_id` (`poll_id`),
  ADD KEY `fk_parent_comment` (`parent_comment_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`UserID`,`PollID`),
  ADD KEY `fk_poll` (`PollID`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`FollowerID`,`FollowingID`),
  ADD UNIQUE KEY `unique_follows` (`FollowerID`,`FollowingID`),
  ADD KEY `fk_following` (`FollowingID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `fk_notification_user` (`user_id`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`poll_id`),
  ADD KEY `Foreign Key Constraint_1` (`CategoryID`),
  ADD KEY `CreatedBy` (`CreatedBy`);

--
-- Indexes for table `profilepictures`
--
ALTER TABLE `profilepictures`
  ADD PRIMARY KEY (`picture_id`),
  ADD KEY `fk_const` (`user_id`);

--
-- Indexes for table `responses`
--
ALTER TABLE `responses`
  ADD PRIMARY KEY (`response_id`),
  ADD KEY `poll_id` (`poll_id`),
  ADD KEY `answer_id` (`answer_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `answers`
--
ALTER TABLE `answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `poll_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `profilepictures`
--
ALTER TABLE `profilepictures`
  MODIFY `picture_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `responses`
--
ALTER TABLE `responses`
  MODIFY `response_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `answers`
--
ALTER TABLE `answers`
  ADD CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`poll_id`);

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`poll_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_parent_comment` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_poll` FOREIGN KEY (`PollID`) REFERENCES `polls` (`poll_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `fk_follower` FOREIGN KEY (`FollowerID`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_following` FOREIGN KEY (`FollowingID`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `polls`
--
ALTER TABLE `polls`
  ADD CONSTRAINT `Foreign Key Constraint_1` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `polls_creator_fk` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `profilepictures`
--
ALTER TABLE `profilepictures`
  ADD CONSTRAINT `fk_const` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `responses`
--
ALTER TABLE `responses`
  ADD CONSTRAINT `responses_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`poll_id`),
  ADD CONSTRAINT `responses_ibfk_2` FOREIGN KEY (`answer_id`) REFERENCES `answers` (`answer_id`),
  ADD CONSTRAINT `responses_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
