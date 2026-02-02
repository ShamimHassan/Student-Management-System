-- Database updates for registration feature

-- Ensure the admins table has proper constraints
ALTER TABLE `admins` 
MODIFY COLUMN `role` ENUM('admin','student') NOT NULL DEFAULT 'admin',
MODIFY COLUMN `student_id` INT NULL;

-- Add indexes for better performance
ALTER TABLE `admins` ADD INDEX `idx_username` (`username`);
ALTER TABLE `admins` ADD INDEX `idx_role` (`role`);
ALTER TABLE `admins` ADD INDEX `idx_student_id` (`student_id`);

-- Add foreign key constraint if not exists
ALTER TABLE `admins` 
ADD CONSTRAINT IF NOT EXISTS `fk_admins_student` 
FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL;

-- Ensure students table has proper constraints
ALTER TABLE `students` 
MODIFY COLUMN `status` ENUM('active','inactive') NOT NULL DEFAULT 'active';

-- Add indexes to students table
ALTER TABLE `students` ADD INDEX `idx_student_id` (`student_id`);
ALTER TABLE `students` ADD INDEX `idx_email` (`email`);
ALTER TABLE `students` ADD INDEX `idx_status` (`status`);

-- Add indexes to other tables for better performance
ALTER TABLE `courses` ADD INDEX `idx_course_code` (`course_code`);
ALTER TABLE `student_courses` ADD INDEX `idx_student_course` (`student_id`, `course_id`);
ALTER TABLE `results` ADD INDEX `idx_student_exam` (`student_id`, `exam_name`);
ALTER TABLE `attendance` ADD INDEX `idx_student_date` (`student_id`, `attendance_date`);
ALTER TABLE `payments` ADD INDEX `idx_student_status` (`student_id`, `status`);