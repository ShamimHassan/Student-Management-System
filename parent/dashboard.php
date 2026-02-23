<?php
$page_title = "Parent Dashboard";
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in and is parent
if (!isLoggedIn() || $_SESSION['user_role'] !== 'parent') {
    header('Location: ../login.php');
    exit();
}

requireLogin();

// Get parent information and linked student
$parent_id = $_SESSION['user_id'];
$parent_query = "SELECT a.*, s.student_id as linked_student_id, s.first_name as student_first_name, s.last_name as student_last_name, 
                 s.email as student_email, s.phone as student_phone
                 FROM users a 
                 JOIN students s ON a.student_id = s.id 
                 WHERE a.id = ?";
$stmt = $conn->prepare($parent_query);
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

if (!$parent) {
    // Parent exists but no student linked
    $message = "No student linked to your account. Please contact administrator.";
}

// Get student's results
if ($parent) {
    $results_query = "SELECT r.*, c.course_name, c.course_code 
                      FROM results r 
                      JOIN courses c ON r.course_id = c.id 
                      WHERE r.student_id = ? 
                      ORDER BY r.exam_date DESC 
                      LIMIT 5";
    $results_stmt = $conn->prepare($results_query);
    $results_stmt->bind_param("i", $parent['linked_student_id']);
    $results_stmt->execute();
    $results = $results_stmt->get_result();

    // Get student's attendance
    $attendance_query = "SELECT c.course_name, c.course_code,
                            COUNT(*) as total_classes,
                            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent
                         FROM attendance a 
                         JOIN courses c ON a.course_id = c.id 
                         WHERE a.student_id = ? 
                         GROUP BY a.course_id 
                         ORDER BY c.course_name";
    $attendance_stmt = $conn->prepare($attendance_query);
    $attendance_stmt->bind_param("i", $parent['linked_student_id']);
    $attendance_stmt->execute();
    $attendance = $attendance_stmt->get_result();

    // Get student's payments
    $payments_query = "SELECT p.*, c.course_name, c.course_code 
                       FROM payments p 
                       JOIN courses c ON p.course_id = c.id 
                       WHERE p.student_id = ? 
                       ORDER BY p.payment_date DESC 
                       LIMIT 5";
    $payments_stmt = $conn->prepare($payments_query);
    $payments_stmt->bind_param("i", $parent['linked_student_id']);
    $payments_stmt->execute();
    $payments = $payments_stmt->get_result();

    // Calculate overall statistics
    $total_courses = $attendance->num_rows;
    $overall_attendance = 0;
    if ($total_courses > 0) {
        $total_present = 0;
        $total_classes = 0;
        $attendance->data_seek(0); // Reset pointer
        while($att = $attendance->fetch_assoc()) {
            $total_present += $att['present'];
            $total_classes += $att['total_classes'];
        }
        $overall_attendance = $total_classes > 0 ? ($total_present / $total_classes) * 100 : 0;
    }
    
    // Reset pointer for display
    $attendance->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Parent Panel</title>
    
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
            background: linear-gradient(180deg, #36b9cc 0%, #1a8caa 100%);
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
                        <h4><i class="fas fa-user-friends me-2"></i>Parent Panel</h4>
                        <small><?php echo htmlspecialchars($_SESSION['user_username']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_results.php">
                                <i class="fas fa-chart-bar"></i> View Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_attendance.php">
                                <i class="fas fa-calendar-check"></i> Attendance Report
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_payments.php">
                                <i class="fas fa-money-bill"></i> Payment History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="generate_reports.php">
                                <i class="fas fa-file-download"></i> Download Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="communication.php">
                                <i class="fas fa-comments"></i> Communication
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="calendar.php">
                                <i class="fas fa-calendar-alt"></i> Institute Calendar
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
                                <i class="fas fa-user-friends me-1"></i>
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
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['user_username']); ?>!</h2>
                                        <?php if ($parent): ?>
                                            <p class="mb-0">Monitoring: <?php echo htmlspecialchars($parent['student_first_name'] . ' ' . $parent['student_last_name']); ?></p>
                                            <p class="mb-0">Student ID: <?php echo htmlspecialchars($parent['linked_student_id']); ?></p>
                                        <?php else: ?>
                                            <p class="mb-0"><?php echo $message; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-end">
                                        <i class="fas fa-user-friends fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($parent): ?>
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Overall Attendance</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($overall_attendance, 1); ?>%
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
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Courses</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_courses; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
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
                                            Recent Results</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $results->num_rows; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
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
                                            Payment Status</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php 
                                            $paid_count = 0;
                                            $payments->data_seek(0);
                                            while($payment = $payments->fetch_assoc()) {
                                                if ($payment['status'] === 'paid') $paid_count++;
                                            }
                                            $payments->data_seek(0);
                                            echo $payments->num_rows > 0 ? "$paid_count/" . $payments->num_rows : "0/0";
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-money-bill fa-2x text-gray-300"></i>
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
                                        <a href="view_results.php" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-chart-bar me-2"></i>View Results
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view_attendance.php" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-calendar-check me-2"></i>Attendance Report
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="view_payments.php" class="btn btn-info btn-lg w-100">
                                            <i class="fas fa-money-bill me-2"></i>Payment History
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="generate_reports.php" class="btn btn-warning btn-lg w-100">
                                            <i class="fas fa-file-download me-2"></i>Download Reports
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Student Information</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars($parent['student_first_name'] . ' ' . $parent['student_last_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Student ID:</strong></td>
                                        <td><?php echo htmlspecialchars($parent['linked_student_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($parent['student_email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phone:</strong></td>
                                        <td><?php echo htmlspecialchars($parent['student_phone']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Overall Performance</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6>Attendance Rate</h6>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-<?php echo $overall_attendance >= 75 ? 'success' : ($overall_attendance >= 50 ? 'warning' : 'danger'); ?>" 
                                             role="progressbar" style="width: <?php echo $overall_attendance; ?>%">
                                            <?php echo number_format($overall_attendance, 1); ?>%
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6>Payment Status</h6>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-<?php echo $paid_count == $payments->num_rows ? 'success' : 'warning'; ?>" 
                                             role="progressbar" style="width: <?php echo $payments->num_rows > 0 ? ($paid_count / $payments->num_rows) * 100 : 0; ?>%">
                                            <?php echo $payments->num_rows > 0 ? "$paid_count/" . $payments->num_rows : "0/0"; ?> Paid
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Recent Exam Results</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($results->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Exam</th>
                                                    <th>Marks</th>
                                                    <th>Grade</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($result = $results->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($result['course_code'] . ' - ' . $result['course_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
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
                                    <p class="text-muted text-center py-4">No exam results found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Attendance Summary</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($attendance->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Total Classes</th>
                                                    <th>Present</th>
                                                    <th>Absent</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($att = $attendance->fetch_assoc()): 
                                                    $percentage = $att['total_classes'] > 0 ? ($att['present'] / $att['total_classes']) * 100 : 0;
                                                    $bar_class = $percentage >= 75 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($att['course_code'] . ' - ' . $att['course_name']); ?></td>
                                                        <td><?php echo $att['total_classes']; ?></td>
                                                        <td><?php echo $att['present']; ?></td>
                                                        <td><?php echo $att['absent']; ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                                    <div class="progress-bar <?php echo $bar_class; ?>" 
                                                                         style="width: <?php echo $percentage; ?>%"></div>
                                                                </div>
                                                                <span><?php echo number_format($percentage, 1); ?>%</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center py-4">No attendance records found.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Features Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-star me-2"></i>Parent Portal Features</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <h5><i class="fas fa-bell me-2 text-primary"></i>Real-time Updates</h5>
                                                <p class="mb-2">Stay informed with real-time activity in class and campus directly on your smartphone.</p>
                                                <ul class="mb-0">
                                                    <li>Daily attendance alerts</li>
                                                    <li>Homework notifications</li>
                                                    <li>Instant exam result updates</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <h5><i class="fas fa-mobile-alt me-2 text-success"></i>Mobile Access</h5>
                                                <p class="mb-2">Access all student management features through web and mobile phone anytime, anywhere.</p>
                                                <ul class="mb-0">
                                                    <li>Cloud-based solution</li>
                                                    <li>Cross-platform compatibility</li>
                                                    <li>24/7 access to student information</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <h5><i class="fas fa-credit-card me-2 text-warning"></i>Payment Management</h5>
                                                <p class="mb-2">Easy fee management with secure online payment processing.</p>
                                                <ul class="mb-0">
                                                    <li>Pay Fee Bill online</li>
                                                    <li>Generate Fee Receipts</li>
                                                    <li>Payment history tracking</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <h5><i class="fas fa-comments me-2 text-info"></i>Communication</h5>
                                                <p class="mb-2">Two-way communication with teachers and school administration.</p>
                                                <ul class="mb-0">
                                                    <li>Direct messaging with teachers</li>
                                                    <li>Institute calendar events</li>
                                                    <li>Announcements and notifications</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- No student linked message -->
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <h4><i class="fas fa-exclamation-triangle me-2"></i>No Student Linked</h4>
                            <p><?php echo $message; ?></p>
                            <p>Please contact the administrator to link your account with a student.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
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