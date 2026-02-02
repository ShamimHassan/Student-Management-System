<?php
$page_title = "Dashboard";
require_once '../includes/header.php';
requireLogin();

// Get statistics
$student_count = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'")->fetch_assoc()['count'];
$course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$result_count = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM results")->fetch_assoc()['count'];
$payment_due = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'due'")->fetch_assoc()['count'];
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
        </h1>
        <p class="text-muted">Welcome back, <?php echo $user['username']; ?>!</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3><?php echo $student_count; ?></h3>
                        <p class="mb-0">Active Students</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-graduate fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3><?php echo $course_count; ?></h3>
                        <p class="mb-0">Courses</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-book fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3><?php echo $result_count; ?></h3>
                        <p class="mb-0">Students with Results</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3><?php echo $payment_due; ?></h3>
                        <p class="mb-0">Pending Payments</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
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
                        <a href="students.php" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus me-2"></i>Add Student
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="courses.php" class="btn btn-success w-100">
                            <i class="fas fa-book-open me-2"></i>Add Course
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="results.php" class="btn btn-info w-100">
                            <i class="fas fa-plus-circle me-2"></i>Add Result
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="attendance.php" class="btn btn-warning w-100">
                            <i class="fas fa-calendar-plus me-2"></i>Mark Attendance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Students</h5>
            </div>
            <div class="card-body">
                <?php
                $recent_students = $conn->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5");
                if ($recent_students->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = $recent_students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $student['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No students found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Recent Courses</h5>
            </div>
            <div class="card-body">
                <?php
                $recent_courses = $conn->query("SELECT * FROM courses ORDER BY created_at DESC LIMIT 5");
                if ($recent_courses->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Credits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($course = $recent_courses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo $course['credits']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No courses found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>