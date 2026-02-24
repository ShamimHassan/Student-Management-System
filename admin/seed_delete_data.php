<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}
$conn->begin_transaction();
try {
    $conn->query("DELETE FROM attendance WHERE course_id IN (SELECT id FROM courses WHERE course_code IN ('BNG101','ENG101','MAT101','SCI101','ICT101')) AND student_id IN (SELECT id FROM students WHERE email LIKE '%@example.com' AND student_id BETWEEN 'STU0001' AND 'STU0200')");
    $conn->query("DELETE FROM payments WHERE course_id IN (SELECT id FROM courses WHERE course_code IN ('BNG101','ENG101','MAT101','SCI101','ICT101')) AND student_id IN (SELECT id FROM students WHERE email LIKE '%@example.com' AND student_id BETWEEN 'STU0001' AND 'STU0200')");
    $conn->query("DELETE FROM results WHERE course_id IN (SELECT id FROM courses WHERE course_code IN ('BNG101','ENG101','MAT101','SCI101','ICT101')) AND student_id IN (SELECT id FROM students WHERE email LIKE '%@example.com' AND student_id BETWEEN 'STU0001' AND 'STU0200')");
    $conn->query("DELETE FROM student_courses WHERE course_id IN (SELECT id FROM courses WHERE course_code IN ('BNG101','ENG101','MAT101','SCI101','ICT101')) AND student_id IN (SELECT id FROM students WHERE email LIKE '%@example.com' AND student_id BETWEEN 'STU0001' AND 'STU0200')");
    $conn->query("DELETE FROM users WHERE role='teacher' AND username IN ('teacher_bangla','teacher_english','teacher_math','teacher_science','teacher_ict')");
    $conn->query("DELETE FROM students WHERE email LIKE '%@example.com' AND student_id BETWEEN 'STU0001' AND 'STU0200'");
    $conn->query("DELETE FROM courses WHERE course_code IN ('BNG101','ENG101','MAT101','SCI101','ICT101')");
    $conn->commit();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Demo data cleared']);
} catch (Exception $e) {
    $conn->rollback();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Clear failed']);
}
