<?php
$page_title = "Student Dashboard";
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in and is student
if (!isLoggedIn() || $_SESSION['user_role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

requireLogin();

// Get student information
$student_id = $_SESSION['user_id'];
$student_query = "SELECT u.*, s.* FROM users u JOIN students s ON u.student_id = s.id WHERE u.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get student's courses
$courses_query = "SELECT c.*, sc.enrollment_date 
                  FROM courses c 
                  JOIN student_courses sc ON c.id = sc.course_id 
                  WHERE sc.student_id = ? 
                  ORDER BY c.course_name";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $student['student_id']);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();

// Get attendance summary
$attendance_query = "SELECT 
                     COUNT(*) as total_classes,
                     SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                     SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                     FROM attendance a 
                     JOIN students s ON a.student_id = s.id 
                     WHERE s.id = ?";
$attendance_stmt = $conn->prepare($attendance_query);
$attendance_stmt->bind_param("i", $student['student_id']);
$attendance_stmt->execute();
$attendance_summary = $attendance_stmt->get_result()->fetch_assoc();

$attendance_percentage = $attendance_summary['total_classes'] > 0 ? 
    ($attendance_summary['present_count'] / $attendance_summary['total_classes']) * 100 : 0;

// Get recent exam results
$results_query = "SELECT r.*, c.course_name 
                  FROM results r 
                  JOIN courses c ON r.course_id = c.id 
                  WHERE r.student_id = ? 
                  ORDER BY r.exam_date DESC 
                  LIMIT 5";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("i", $student['student_id']);
$results_stmt->execute();
$recent_results = $results_stmt->get_result();

// Get recent notifications for this student
$notifications_query = "SELECT n.*, u.username 
                       FROM notifications n 
                       JOIN users u ON n.user_id = u.id 
                       WHERE n.user_id = ? OR n.user_id IN (
                           SELECT id FROM users WHERE student_id = ?
                       )
                       ORDER BY n.created_at DESC 
                       LIMIT 5";
$notifications_stmt = $conn->prepare($notifications_query);
$notifications_stmt->bind_param("ii", $student_id, $student['student_id']);
$notifications_stmt->execute();
$notifications = $notifications_stmt->get_result();

// Get fees information
$fees_query = "SELECT p.*, c.course_name 
               FROM payments p 
               JOIN courses c ON p.course_id = c.id 
               WHERE p.student_id = ? 
               ORDER BY p.payment_date DESC 
               LIMIT 5";
$fees_stmt = $conn->prepare($fees_query);
$fees_stmt->bind_param("i", $student['student_id']);
$fees_stmt->execute();
$recent_payments = $fees_stmt->get_result();

// Calculate total due amount
$due_query = "SELECT SUM(p.amount) as total_due 
              FROM payments p 
              WHERE p.student_id = ? AND p.status = 'due'";
$due_stmt = $conn->prepare($due_query);
$due_stmt->bind_param("i", $student['student_id']);
$due_stmt->execute();
$total_due = $due_stmt->get_result()->fetch_assoc()['total_due'] ?: 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Student Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fc;
        }
        .sidebar {
            background: linear-gradient(180deg, #1cc88a 0%, #13855c 100%);
            color: white;
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 5px 10px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
        }
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-user-graduate me-2"></i>Student Panel</h4>
                        <small><?php echo htmlspecialchars($_SESSION['user_username']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_courses.php">
                                <i class="fas fa-book"></i> My Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_results.php">
                                <i class="fas fa-chart-bar"></i> My Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_attendance.php">
                                <i class="fas fa-calendar-check"></i> My Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pay_fees.php">
                                <i class="fas fa-money-bill-wave"></i> Pay Fees
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user"></i> Profile
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="my-4">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Top Navigation -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded mb-4">
                    <div class="container-fluid">
                        <button class="btn btn-link d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div class="d-flex align-items-center">
                            <h5 class="mb-0"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h5>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <span class="me-3">
                                <i class="fas fa-user-graduate me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_username']); ?>
                            </span>
                            <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>!</h2>
                                        <p class="mb-0">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
                                        <p class="mb-0">Keep class and institute activities at your fingertips</p>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-user-graduate fa-3x"></i>
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
                                            Courses Enrolled</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $courses->num_rows; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
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
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Recent Results</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $recent_results->num_rows; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
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
                                            Due Amount</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">৳<?php echo number_format($total_due, 2); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
                                        <a href="my_courses.php" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-book me-2"></i>My Courses
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="my_results.php" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-chart-bar me-2"></i>My Results
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="my_attendance.php" class="btn btn-info btn-lg w-100">
                                            <i class="fas fa-calendar-check me-2"></i>My Attendance
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="pay_fees.php" class="btn btn-warning btn-lg w-100">
                                            <i class="fas fa-money-bill-wave me-2"></i>Pay Fees
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-book me-2"></i>My Courses</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($courses->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Course</th>
                                                    <th>Credits</th>
                                                    <th>Enrolled</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($course = $courses->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                        <td><?php echo $course['credits']; ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($course['enrollment_date'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No courses enrolled yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <h3 class="text-success"><?php echo $attendance_summary['present_count']; ?></h3>
                                    <p class="text-muted">Days Present</p>
                                </div>
                                
                                <div class="text-center mb-4">
                                    <h3 class="text-danger"><?php echo $attendance_summary['absent_count']; ?></h3>
                                    <p class="text-muted">Days Absent</p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6>Overall Attendance</h6>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $attendance_percentage; ?>%">
                                            <?php echo number_format($attendance_percentage, 1); ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $attendance_summary['total_classes']; ?> total days
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results and Notifications -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Recent Exam Results</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_results->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Marks</th>
                                                    <th>Grade</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($result = $recent_results->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                                        <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo strpos($result['grade'], '5.00') !== false ? 'success' : 
                                                                    (strpos($result['grade'], '4.00') !== false ? 'primary' : 
                                                                    (strpos($result['grade'], '3.50') !== false ? 'info' : 
                                                                    (strpos($result['grade'], '3.00') !== false ? 'warning' : 'danger'))); ?>">
                                                                <?php echo $result['grade']; ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M j, Y', strtotime($result['exam_date'])); ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No exam results yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($notifications->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while($notification = $notifications->fetch_assoc()): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                    <small><?php echo date('M j, g:i A', strtotime($notification['created_at'])); ?></small>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted">
                                                    Type: <?php echo ucfirst($notification['type']); ?>
                                                </small>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No notifications yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fee Information -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Recent Payments</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($recent_payments->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Amount (৳)</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($payment = $recent_payments->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($payment['course_name']); ?></td>
                                                        <td><?php echo number_format($payment['amount'], 2); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $payment['status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                                <?php echo ucfirst($payment['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No payment records found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Grading System Information -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Grading System (5.0 Scale)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="alert alert-success">
                                            <h6>5.00 (A+): 80–100%</h6>
                                            <p class="mb-0">Excellent performance</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-primary">
                                            <h6>4.00 (A): 70–79%</h6>
                                            <p class="mb-0">Very good performance</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-info">
                                            <h6>3.50 (A-): 60–69%</h6>
                                            <p class="mb-0">Good performance</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="alert alert-warning">
                                            <h6>3.00 (B): 50–59%</h6>
                                            <p class="mb-0">Satisfactory performance</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-secondary">
                                            <h6>2.00 (C): 40–49%</h6>
                                            <p class="mb-0">Basic performance</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-danger">
                                            <h6>1.00 (D): 33–39%</h6>
                                            <p class="mb-0">Minimum passing</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-dark">
                                            <h6>0.00 (F): 0–32%</h6>
                                            <p class="mb-0">Fail - Below minimum requirements</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- All Courses Section -->
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>All Courses</h5>
            </div>
            <div class="card-body">
                <?php 
                $all_courses = $conn->query("SELECT * FROM courses ORDER BY created_at DESC");
                if ($all_courses->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Fee (tk)</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($course = $all_courses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['description']); ?></td>
                                <td><?php echo number_format($course['fee'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-info">No courses found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
</body>
</html>