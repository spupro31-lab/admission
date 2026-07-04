


CREATE DATABASE IF NOT EXISTS `student_admission_db`;
USE `student_admission_db`;


DROP TABLE IF EXISTS `status_history`;
DROP TABLE IF EXISTS `documents`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `admission_staff`;
DROP TABLE IF EXISTS `users`;


CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL, 
    `role` ENUM('admin', 'student') NOT NULL DEFAULT 'student'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `courses` (
    `course_id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_name` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `total_seats` INT NOT NULL DEFAULT 60
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS `students` (
    `student_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `admission_no` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `father_name` VARCHAR(100) NOT NULL,
    `mother_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `dob` DATE NOT NULL,
    `category` VARCHAR(50) NOT NULL, 
    `mobile` VARCHAR(15) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `address` TEXT NOT NULL,
    `city` VARCHAR(50) NOT NULL,
    `state` VARCHAR(50) NOT NULL,
    `pincode` VARCHAR(10) NOT NULL,
    `tenth_percentage` DECIMAL(5,2) NOT NULL,
    `twelfth_percentage` DECIMAL(5,2) NOT NULL,
    `school_name` VARCHAR(150) NOT NULL,
    `passing_year` INT NOT NULL,
    `course_id` INT DEFAULT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `is_submitted` TINYINT(1) NOT NULL DEFAULT 0,
    `payment_status` ENUM('Unpaid', 'Paid') NOT NULL DEFAULT 'Unpaid',
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS `documents` (
    `document_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `photo` VARCHAR(255) DEFAULT NULL,
    `marksheet10` VARCHAR(255) DEFAULT NULL,
    `marksheet12` VARCHAR(255) DEFAULT NULL,
    `leaving_certificate` VARCHAR(255) DEFAULT NULL,
    `aadhaar` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS `admission_staff` (
    `staff_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS `status_history` (
    `history_id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL,
    `remarks` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;






INSERT INTO `courses` (`course_name`, `department`, `semester`, `total_seats`) VALUES
('B.Sc. Computer Science', 'Science & IT', 'Semester I', 60),
('Bachelor of Computer Applications (BCA)', 'Science & IT', 'Semester I', 80),
('B.Com. (General)', 'Commerce', 'Semester I', 120),
('B.A. English Literature', 'Arts & Humanities', 'Semester I', 60),
('B.Sc. Information Technology (B.Sc. IT)', 'Science & IT', 'Semester I', 60);




INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Default Admin', 'admin@college.com', '$2y$10$rSNB6RwmyfsrTTiOiw846esSVblz.vyVdf1lpGocM4WrOWkGTvc2O', 'admin');




INSERT IGNORE INTO `admission_staff` (`name`, `email`, `password`) VALUES
('John Staff', 'staff@college.com', '$2y$10$vvNkcYnqOez7rzt3TzKEgeLkLZldxaxpnIS6PHSWzlfeiNN.QbZ1.');
