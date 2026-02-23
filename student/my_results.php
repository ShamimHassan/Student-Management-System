<?php
$page_title = "My Results";
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

// Get student's results
$results_query = "SELECT r.*, c.course_name, c.course_code, c.credits
                  FROM results r 
                  JOIN courses c ON r.course_id = c.id 
                  WHERE r.student_id = ? 
                  ORDER BY r.exam_date DESC";
$results_stmt = $conn->prepare($results_query);
$results_stmt->bind_param("i", $student['student_id']);
$results_stmt->execute();
$results = $results_stmt->get_result();

// Get cumulative GPA
$gpa_query = "SELECT AVG(CASE 
                      WHEN r.grade = '5.00 (A+)' THEN 5.00
                      WHEN r.grade = '4.00 (A)' THEN 4.00
                      WHEN r.grade = '3.50 (A-)' THEN 3.50
                      WHEN r.grade = '3.00 (B)' THEN 3.00
                      WHEN r.grade = '2.00 (C)' THEN 2.00
                      WHEN r.grade = '1.00 (D)' THEN 1.00
                      WHEN r.grade = '0.00 (F)' THEN 0.00
                      ELSE 0
                    END) as cumulative_gpa,
                    COUNT(r.id) as total_courses
                 FROM results r 
                 WHERE r.student_id = ?";
$gpa_stmt = $conn->prepare($gpa_query);
$gpa_stmt->bind_param("i", $student['student_id']);
$gpa_stmt->execute();
$gpa_result = $gpa_stmt->get_result()->fetch_assoc();

$cumulative_gpa = $gpa_result['cumulative_gpa'] ?: 0;
$total_courses = $gpa_result['total_courses'];
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
        .gpa-card {
            text-align: center;
            padding: 20px;
        }
        .gpa-number {
            font-size: 3rem;
            font-weight: bold;
            color: #4e73df;
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_courses.php">
                                <i class="fas fa-book"></i> My Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my_results.php">
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
                            <h5 class="mb-0"><?php echo isset($page_title) ? $page_title : 'Page'; ?></h5>
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
                
                <!-- Page Content -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card gpa-card">
                            <h5>Cumulative GPA</h5>
                            <div class="gpa-number"><?php echo number_format($cumulative_gpa, 2); ?></div>
                            <p class="text-muted">Based on <?php echo $total_courses; ?> courses</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card gpa-card">
                            <h5>Total Courses</h5>
                            <div class="gpa-number"><?php echo $total_courses; ?></div>
                            <p class="text-muted">Completed & Evaluated</p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card gpa-card">
                            <h5>Standing</h5>
                            <div class="gpa-number">
                                <?php
                                if ($cumulative_gpa >= 4.50) {
                                    echo '<span class="text-success">Excellent</span>';
                                } elseif ($cumulative_gpa >= 3.50) {
                                    echo '<span class="text-primary">Good</span>';
                                } elseif ($cumulative_gpa >= 2.50) {
                                    echo '<span class="text-info">Average</span>';
                                } elseif ($cumulative_gpa >= 2.00) {
                                    echo '<span class="text-warning">Below Average</span>';
                                } else {
                                    echo '<span class="text-danger">Poor</span>';
                                }
                                ?>
                            </div>
                            <p class="text-muted">Academic Performance</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>My Exam Results</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-print me-1"></i> Print Report
                                    </button>
                                    <button class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download me-1"></i> Download PDF
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($results->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Course Code</th>
                                                    <th>Course Name</th>
                                                    <th>Marks</th>
                                                    <th>Grade</th>
                                                    <th>Exam Date</th>
                                                    <th>Teacher</th>
                                                    <th>Credit</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($result = $results->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($result['course_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($result['course_name']); ?></td>
                                                    <td><?php echo $result['marks_obtained']; ?> / <?php echo $result['total_marks']; ?></td>
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
                                                    <td>
                                                        <?php
                                                        $teacher_query = "SELECT u.first_name, u.last_name FROM users u WHERE u.subject_id = ?";
                                                        $teacher_stmt = $conn->prepare($teacher_query);
                                                        $teacher_stmt->bind_param("i", $result['course_id']);
                                                        $teacher_stmt->execute();
                                                        $teacher_result = $teacher_stmt->get_result();
                                                        if ($teacher = $teacher_result->fetch_assoc()) {
                                                            echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']);
                                                        } else {
                                                            echo 'N/A';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo $result['credits']; ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="view_result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="download_result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-primary" title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                        <h5>No results found</h5>
                                        <p class="text-muted">You haven't received any exam results yet.</p>
                                        <a href="upcoming_exams.php" class="btn btn-primary">Upcoming Exams</a>
                                    </div>
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

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
</body>
</html>