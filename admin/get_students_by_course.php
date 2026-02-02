<?php
require_once '../includes/config.php';
requireLogin();

$course_id = $_POST['course_id'] ?? 0;

if ($course_id) {
    $sql = "SELECT s.id, s.student_id, s.first_name, s.last_name 
            FROM students s 
            JOIN student_courses sc ON s.id = sc.student_id 
            WHERE sc.course_id = ? AND s.status = 'active' 
            ORDER BY s.first_name, s.last_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $students = $stmt->get_result();
    
    if ($students->num_rows > 0) {
        while($student = $students->fetch_assoc()) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
            echo '<td>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</td>';
            echo '<td><input type="radio" name="attendance[' . $student['id'] . ']" value="present" required></td>';
            echo '<td><input type="radio" name="attendance[' . $student['id'] . ']" value="absent" required></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" class="text-center">No students enrolled in this course</td></tr>';
    }
} else {
    echo '<tr><td colspan="4" class="text-center">Please select a course</td></tr>';
}
?>