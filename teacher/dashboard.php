<?php
$page_title = "Teacher Dashboard";
require_once '../includes/header.php';
requireLogin();

// Check if user is teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

// Get teacher information
$teacher_id = $_SESSION['user_id'];
$teacher_query = "SELECT a.*, s.course_name as subject_name FROM admins a LEFT JOIN courses s ON a.subject_id = s.id WHERE a.id = ?";
$stmt = $conn->prepare($teacher_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();

// Get assigned classes count
$assigned_classes = !empty($teacher['assigned_classes']) ? explode(',', $teacher['assigned_classes']) : [];
$classes_count = count($assigned_classes);

// Get students in teacher's subject
$students_query = "SELECT COUNT(DISTINCT sc.student_id) as student_count 
                   FROM student_courses sc 
                   JOIN courses c ON sc.course_id = c.id 
                   WHERE c.id = ?";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $teacher['subject_id']);
$students_stmt->execute();
$student_count = $students_stmt->get_result()->fetch_assoc()['student_count'];

// Get recent exams/assignments
$recent_exams_query = "SELECT r.*, s.first_name, s.last_name, c.course_name 
                       FROM results r 
                       JOIN students s ON r.student_id = s.id 
                       JOIN courses c ON r.course_id = c.id 
                       WHERE c.id = ? 
                       ORDER BY r.created_at DESC 
                       LIMIT 5";
$exams_stmt = $conn->prepare($recent_exams_query);
$exams_stmt->bind_param("i", $teacher['subject_id']);
$exams_stmt->execute();
$recent_exams = $exams_stmt->get_result();

// Get attendance summary for teacher's subject
$attendance_query = "SELECT 
                        COUNT(*) as total_classes,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                     FROM attendance a 
                     JOIN courses c ON a.course_id = c.id 
                     WHERE c.id = ?";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("i", $teacher['subject_id']);
$attendance_stmt->execute();
$attendance_summary = $attendance_stmt->get_result()->fetch_assoc();

$attendance_percentage = $attendance_summary['total_classes'] > 0 ? 
    ($attendance_summary['present_count'] / $attendance_summary['total_classes']) * 100 : 0;
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($teacher['username']); ?>!</h2>
                            <p class="mb-0">Subject: <?php echo $teacher['subject_name'] ? htmlspecialchars($teacher['subject_name']) : 'Not assigned'; ?></p>
                            <p class="mb-0">Classes: <?php echo implode(', ', array_map('trim', $assigned_classes)); ?></p>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-chalkboard-teacher fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $student_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Classes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $classes_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Attendance Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($attendance_percentage, 1); ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Recent Exams</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $recent_exams->num_rows; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="take_exam.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-edit me-2"></i>Take Exam
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="enter_marks.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-pen me-2"></i>Enter Marks
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="attendance.php" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-calendar-check me-2"></i>Attendance
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="view_results.php" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-chart-bar me-2"></i>View Results
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Exam Results</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_exams->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($exam = $recent_exams->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($exam['first_name'] . ' ' . $exam['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($exam['exam_name']); ?></td>
                                            <td><?php echo $exam['marks_obtained']; ?>/<?php echo $exam['total_marks']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo strpos($exam['grade'], '5.00') !== false ? 'success' : 
                                                        (strpos($exam['grade'], '4.00') !== false ? 'primary' : 
                                                        (strpos($exam['grade'], '3.50') !== false ? 'info' : 
                                                        (strpos($exam['grade'], '3.00') !== false ? 'warning' : 'danger'))); ?>">
                                                    <?php echo $exam['grade']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($exam['exam_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No recent exam results found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Subject Overview</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Subject</h6>
                        <p class="mb-2"><?php echo $teacher['subject_name'] ? htmlspecialchars($teacher['subject_name']) : 'Not assigned'; ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Assigned Classes</h6>
                        <?php if (!empty($assigned_classes)): ?>
                            <ul class="list-unstyled">
                                <?php foreach($assigned_classes as $class): ?>
                                    <li><i class="fas fa-chalkboard me-2"></i><?php echo trim($class); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">No classes assigned</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Attendance Summary</h6>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $attendance_percentage; ?>%">
                                <?php echo number_format($attendance_percentage, 1); ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $attendance_summary['present_count']; ?> present out of 
                            <?php echo $attendance_summary['total_classes']; ?> classes
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>