-- Update database schema to support user roles

-- Add role column to admins table
ALTER TABLE `admins` 
ADD COLUMN `role` ENUM('admin', 'student') NOT NULL DEFAULT 'admin' AFTER `password`,
ADD COLUMN `student_id` INT NULL AFTER `role`;

-- Create foreign key constraint for student_id
ALTER TABLE `admins` 
ADD CONSTRAINT `fk_admins_student` 
FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

-- Update existing admin record to have admin role
UPDATE `admins` SET `role` = 'admin' WHERE `id` = 1;

-- Create a sample student account
INSERT INTO `students` (`student_id`, `first_name`, `last_name`, `email`, `phone`, `status`) 
VALUES ('STU001', 'John', 'Doe', 'john.doe@student.com', '123-456-7890', 'active');

-- Get the last inserted student ID
SET @student_id = LAST_INSERT_ID();

-- Create student login account
INSERT INTO `admins` (`username`, `password`, `role`, `student_id`) 
VALUES ('student', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', @student_id);

-- Create indexes for better performance
ALTER TABLE `admins` ADD INDEX `idx_role` (`role`);
ALTER TABLE `admins` ADD INDEX `idx_student_id` (`student_id`);