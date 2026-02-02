<?php
$page_title = "Student Dashboard";
require_once 'header.php';
requireLogin();

$user = getUserDetails($conn);
$student_db_id = $user['id']; // Use the database ID, not student_id string

// Get student's courses
$courses = $conn->query("SELECT c.*, sc.enrollment_date 
                        FROM courses c 
                        JOIN student_courses sc ON c.id = sc.course_id 
                        WHERE sc.student_id = $student_db_id 
                        ORDER BY c.course_name");

// Get student's results
$results = $conn->query("SELECT r.*, c.course_code, c.course_name 
                        FROM results r 
                        JOIN courses c ON r.course_id = c.id 
                        WHERE r.student_id = $student_db_id 
                        ORDER BY r.exam_date DESC");

// Get student's attendance summary
$attendance_summary = $conn->query("SELECT c.course_name, 
                                    COUNT(a.id) as total_classes,
                                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                                    (SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(a.id)) as percentage
                                    FROM attendance a
                                    JOIN courses c ON a.course_id = c.id
                                    WHERE a.student_id = $student_db_id
                                    GROUP BY c.id, c.course_name");

// Get pending payments
$pending_payments = $conn->query("SELECT p.*, c.course_name, c.course_code, c.fee
                                 FROM payments p
                                 JOIN courses c ON p.course_id = c.id
                                 WHERE p.student_id = $student_db_id AND p.status = 'due'");
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">
            <i class="fas fa-user-graduate me-2"></i>Student Dashboard
        </h1>
        <p class="text-muted">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</p>
    </div>
</div>

<!-- Student Info Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Student Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($user['student_id']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['student_email']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?php echo $courses->num_rows; ?></h3>
                <p class="mb-0">Enrolled Courses</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo $results->num_rows; ?></h3>
                <p class="mb-0">Results Available</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $attendance_summary->num_rows; ?></h3>
                <p class="mb-0">Courses with Attendance</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3><?php echo $pending_payments->num_rows; ?></h3>
                <p class="mb-0">Pending Payments</p>
            </div>
        </div>
    </div>
</div>

<!-- My Courses -->
<div class="row mb-4">
    <div class="col-md-6">
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
                                <th>Enrolled</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($course = $courses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($course['enrollment_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">You are not enrolled in any courses yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Results -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Recent Results</h5>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $count = 0;
                            while($result = $results->fetch_assoc() && $count < 5): 
                                $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                                $grade_class = '';
                                switch($result['grade']) {
                                    case 'A+': case 'A': $grade_class = 'success'; break;
                                    case 'B': case 'C': $grade_class = 'primary'; break;
                                    case 'D': $grade_class = 'warning'; break;
                                    case 'F': $grade_class = 'danger'; break;
                                }
                                $count++;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                                <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?></td>
                                <td><span class="badge bg-<?php echo $grade_class; ?>"><?php echo $result['grade']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No results available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Summary -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Attendance Summary</h5>
            </div>
            <div class="card-body">
                <?php if ($attendance_summary->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Total Classes</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($attendance = $attendance_summary->fetch_assoc()): 
                                $absent = $attendance['total_classes'] - $attendance['present'];
                                $percentage = $attendance['percentage'];
                                $bar_class = $percentage >= 75 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attendance['course_name']); ?></td>
                                <td><?php echo $attendance['total_classes']; ?></td>
                                <td class="text-success"><?php echo $attendance['present']; ?></td>
                                <td class="text-danger"><?php echo $absent; ?></td>
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
                <p class="text-muted">No attendance records available yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Pending Payments -->
<?php if ($pending_payments->num_rows > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Pending Payments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Total Fee</th>
                                <th>Amount Paid</th>
                                <th>Amount Due</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($payment = $pending_payments->fetch_assoc()): 
                                $paid_amount = $conn->query("SELECT SUM(amount) as total FROM payments WHERE student_id = $student_db_id AND course_id = {$payment['course_id']} AND status = 'paid'")->fetch_assoc()['total'] ?? 0;
                                $due_amount = $payment['fee'] - $paid_amount;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['course_code'] . ' - ' . $payment['course_name']); ?></td>
                                <td>৳<?php echo number_format($payment['fee'], 2); ?></td>
                                <td>৳<?php echo number_format($paid_amount, 2); ?></td>
                                <td class="text-danger"><strong>৳<?php echo number_format($due_amount, 2); ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'footer.php'; ?>