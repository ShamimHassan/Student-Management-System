<?php
$page_title = "Take Exam";
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in and is teacher
if (!isLoggedIn() || $_SESSION['user_role'] !== 'teacher') {
    header('Location: ../login.php');
    exit();
}

requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Teacher Panel</title>
    
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
            background: linear-gradient(180deg, #4e73df 0%, #224abe 100%);
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-chalkboard-teacher me-2"></i>Teacher Panel</h4>
                        <small><?php echo htmlspecialchars($_SESSION['user_username']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="take_exam.php">
                                <i class="fas fa-edit"></i> Take Exam
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="enter_marks.php">
                                <i class="fas fa-pen"></i> Enter Marks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view_results.php">
                                <i class="fas fa-chart-bar"></i> View Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="student_list.php">
                                <i class="fas fa-users"></i> My Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="communication.php">
                                <i class="fas fa-comments"></i> Communication
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
                                <i class="fas fa-chalkboard-teacher me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_username']); ?>
                            </span>
                            <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- Page Content -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Take Exam</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-5">
                                    <i class="fas fa-edit fa-3x text-primary mb-3"></i>
                                    <h4>Virtual Exam System</h4>
                                    <p class="text-muted">Create and conduct exams with virtual question system</p>
                                    
                                    <div class="row mt-4">
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-plus-circle fa-2x text-success mb-2"></i>
                                                    <h5>Create New Exam</h5>
                                                    <p class="text-muted">Design exam with virtual questions</p>
                                                    <a href="#" class="btn btn-success">Start Creating</a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-list fa-2x text-info mb-2"></i>
                                                    <h5>Manage Exams</h5>
                                                    <p class="text-muted">View and edit existing exams</p>
                                                    <a href="#" class="btn btn-info">View Exams</a>
                                                </div>
                                            </div>
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