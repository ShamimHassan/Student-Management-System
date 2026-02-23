<?php
$page_title = "My Courses";
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
$courses_query = "SELECT c.*, sc.enrollment_date, sc.status as enrollment_status
                  FROM courses c 
                  JOIN student_courses sc ON c.id = sc.course_id 
                  WHERE sc.student_id = ? 
                  ORDER BY c.course_name";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $student['student_id']);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();

// Get course statistics
$stats_query = "SELECT 
                   COUNT(*) as total_courses,
                   SUM(CASE WHEN sc.status = 'completed' THEN 1 ELSE 0 END) as completed_courses,
                   SUM(CASE WHEN sc.status = 'enrolled' THEN 1 ELSE 0 END) as enrolled_courses
                FROM courses c 
                JOIN student_courses sc ON c.id = sc.course_id 
                WHERE sc.student_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $student['student_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my_courses.php">
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
                    <div class="col-md-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Courses</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_courses']; ?></div>
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
                                            Completed</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['completed_courses']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                            Enrolled</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['enrolled_courses']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                            Progress</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $stats['total_courses'] > 0 ? round(($stats['completed_courses'] / $stats['total_courses']) * 100, 1) : 0; ?>%
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>My Courses</h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-2">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <button class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download me-1"></i> Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($courses->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Course Name</th>
                                                    <th>Description</th>
                                                    <th>Credits</th>
                                                    <th>Fee (৳)</th>
                                                    <th>Enrolled</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($course = $courses->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . (strlen($course['description']) > 50 ? '...' : ''); ?></td>
                                                    <td><?php echo $course['credits']; ?></td>
                                                    <td><?php echo number_format($course['fee'], 2); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($course['enrollment_date'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo $course['enrollment_status'] === 'completed' ? 'success' : 
                                                                 ($course['enrollment_status'] === 'enrolled' ? 'primary' : 'warning'); ?>">
                                                            <?php echo ucfirst($course['enrollment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="course_materials.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary" title="Materials">
                                                                <i class="fas fa-folder-open"></i>
                                                            </a>
                                                            <a href="course_assignments.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-warning" title="Assignments">
                                                                <i class="fas fa-tasks"></i>
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
                                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                        <h5>No courses enrolled</h5>
                                        <p class="text-muted">You haven't enrolled in any courses yet.</p>
                                        <a href="available_courses.php" class="btn btn-primary">Browse Available Courses</a>
                                    </div>
                                <?php endif; ?>
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