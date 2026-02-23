<?php
$page_title = "Manage Students";
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

requireLogin();

// Get all students
$students_query = "SELECT s.*, u.username, u.role 
                   FROM students s 
                   LEFT JOIN users u ON s.id = u.student_id 
                   ORDER BY s.created_at DESC";
$students = $conn->query($students_query);

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'delete_student') {
            $student_id = intval($_POST['student_id']);
            
            // Delete related records first
            $conn->begin_transaction();
            try {
                // Delete from student_courses
                $stmt = $conn->prepare("DELETE FROM student_courses WHERE student_id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                
                // Delete from attendance
                $stmt = $conn->prepare("DELETE FROM attendance WHERE student_id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                
                // Delete from results
                $stmt = $conn->prepare("DELETE FROM results WHERE student_id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                
                // Delete from users table if exists
                $stmt = $conn->prepare("DELETE FROM users WHERE student_id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                
                // Finally delete from students table
                $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                
                $conn->commit();
                $message = "Student deleted successfully!";
                $message_type = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error deleting student: " . $e->getMessage();
                $message_type = "danger";
            }
        }
        
        // Refresh the students list
        $students = $conn->query("SELECT s.*, u.username, u.role 
                                  FROM students s 
                                  LEFT JOIN users u ON s.id = u.student_id 
                                  ORDER BY s.created_at DESC");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel</title>
    
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
                        <h4><i class="fas fa-user-shield me-2"></i>Admin Panel</h4>
                        <small><?php echo htmlspecialchars($_SESSION['user_username']); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_students.php">
                                <i class="fas fa-graduation-cap"></i> Manage Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_teachers.php">
                                <i class="fas fa-chalkboard-teacher"></i> Manage Teachers
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_parents.php">
                                <i class="fas fa-user-friends"></i> Manage Parents
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_courses.php">
                                <i class="fas fa-book"></i> Manage Courses
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_attendance.php">
                                <i class="fas fa-calendar-check"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_results.php">
                                <i class="fas fa-chart-bar"></i> Results
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_fees.php">
                                <i class="fas fa-money-bill-wave"></i> Fee Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="virtual_exam.php">
                                <i class="fas fa-file-alt"></i> Virtual Exam
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
                                <i class="fas fa-user-shield me-1"></i>
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
                        <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Manage Students</h5>
                                <a href="add_student.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Add Student
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if ($students->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Student ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Username</th>
                                                    <th>Role</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($student = $students->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['username'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $student['role'] === 'student' ? 'success' : 'secondary'; ?>">
                                                            <?php echo htmlspecialchars($student['role'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($student['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this student?')">
                                                                <input type="hidden" name="action" value="delete_student">
                                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                        <h5>No students found</h5>
                                        <p class="text-muted">No students have been registered yet.</p>
                                        <a href="add_student.php" class="btn btn-primary">Add Student</a>
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