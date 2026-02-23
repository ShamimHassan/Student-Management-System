<?php
$page_title = "Communication";
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
        .chat-container {
            height: 400px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 15px;
        }
        .message-sent {
            text-align: right;
        }
        .message-received {
            text-align: left;
        }
        .message-bubble {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 70%;
        }
        .message-sent .message-bubble {
            background-color: #4e73df;
            color: white;
        }
        .message-received .message-bubble {
            background-color: #e9ecef;
            color: #333;
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
                            <a class="nav-link" href="take_exam.php">
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
                            <a class="nav-link active" href="communication.php">
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
                                <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Communication Center</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Recipients</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="list-group list-group-flush">
                                                    <a href="#" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-user-graduate me-2 text-primary"></i> Students
                                                    </a>
                                                    <a href="#" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-user-friends me-2 text-success"></i> Parents
                                                    </a>
                                                    <a href="#" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-user-tie me-2 text-info"></i> Staff
                                                    </a>
                                                    <a href="#" class="list-group-item list-group-item-action">
                                                        <i class="fas fa-users me-2 text-warning"></i> Groups
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card mt-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Announcements</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#announcementModal">
                                                        <i class="fas fa-bullhorn me-1"></i> Create Announcement
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-8">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h6 class="mb-0">Messages</h6>
                                            </div>
                                            <div class="card-body d-flex flex-column">
                                                <div class="chat-container mb-3">
                                                    <div class="message message-received">
                                                        <div class="message-bubble">
                                                            Hello! How can I help you today?
                                                        </div>
                                                        <small class="text-muted">Admin • 10:30 AM</small>
                                                    </div>
                                                    <div class="message message-sent">
                                                        <div class="message-bubble">
                                                            I wanted to discuss the upcoming exam schedule.
                                                        </div>
                                                        <small class="text-muted float-end">You • 10:32 AM</small>
                                                    </div>
                                                    <div class="message message-received">
                                                        <div class="message-bubble">
                                                            Sure, what specifically do you need to know?
                                                        </div>
                                                        <small class="text-muted">Admin • 10:33 AM</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-auto">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" placeholder="Type your message...">
                                                        <button class="btn btn-primary" type="button">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                    </div>
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

    <!-- Announcement Modal -->
    <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="announcementModalLabel">Create Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="announcementTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="announcementTitle" placeholder="Enter announcement title">
                        </div>
                        <div class="mb-3">
                            <label for="announcementContent" class="form-label">Content</label>
                            <textarea class="form-control" id="announcementContent" rows="5" placeholder="Enter announcement content"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="recipientType" class="form-label">Send To</label>
                            <select class="form-select" id="recipientType">
                                <option value="students">Students</option>
                                <option value="parents">Parents</option>
                                <option value="staff">Staff</option>
                                <option value="all">All Users</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="urgentCheck">
                            <label class="form-check-label" for="urgentCheck">Mark as Urgent</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Send Announcement</button>
                </div>
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