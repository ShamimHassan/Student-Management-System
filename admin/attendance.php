<?php
$page_title = "Attendance Management";
require_once '../includes/header.php';
requireLogin();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_attendance':
                $date = $_POST['attendance_date'];
                $course_id = $_POST['course_id'];
                $attendance_data = $_POST['attendance'];
                
                $success_count = 0;
                $error_count = 0;
                
                foreach ($attendance_data as $student_id => $status) {
                    // Check if attendance already exists for this student, course, and date
                    $check = $conn->prepare("SELECT id FROM attendance WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
                    $check->bind_param("iis", $student_id, $course_id, $date);
                    $check->execute();
                    
                    if ($check->get_result()->num_rows > 0) {
                        // Update existing attendance
                        $stmt = $conn->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND course_id = ? AND attendance_date = ?");
                        $stmt->bind_param("siis", $status, $student_id, $course_id, $date);
                    } else {
                        // Insert new attendance
                        $stmt = $conn->prepare("INSERT INTO attendance (student_id, course_id, attendance_date, status) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiss", $student_id, $course_id, $date, $status);
                    }
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
                
                if ($success_count > 0) {
                    $message = "$success_count attendance records updated successfully!";
                    $message_type = "success";
                }
                if ($error_count > 0) {
                    $message .= " $error_count records failed to update.";
                    $message_type = "warning";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Attendance record deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting attendance record!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get data for dropdowns
$courses = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");

// Get attendance data
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$sql = "SELECT a.*, s.student_id, s.first_name, s.last_name, c.course_code, c.course_name 
        FROM attendance a 
        JOIN students s ON a.student_id = s.id 
        JOIN courses c ON a.course_id = c.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

if (!empty($course_filter)) {
    $sql .= " AND a.course_id = ?";
    $params[] = $course_filter;
    $types .= "i";
}

if (!empty($date_filter)) {
    $sql .= " AND a.attendance_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$sql .= " ORDER BY s.first_name, s.last_name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendance = $stmt->get_result();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-calendar-check me-2"></i>Attendance Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#markAttendanceModal">
                <i class="fas fa-plus me-1"></i>Mark Attendance
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-md-3">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search students..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-3">
        <form method="GET">
            <select name="course_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Courses</option>
                <?php 
                $course_list = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");
                while($course = $course_list->fetch_assoc()): 
                ?>
                    <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            <?php if (!empty($date_filter)): ?>
            <input type="hidden" name="date" value="<?php echo $date_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-3">
        <form method="GET">
            <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
            <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            <?php if (!empty($course_filter)): ?>
            <input type="hidden" name="course_id" value="<?php echo $course_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-3">
        <div class="d-grid">
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#attendanceReportModal">
                <i class="fas fa-chart-bar me-1"></i>View Report
            </button>
        </div>
    </div>
</div>

<!-- Attendance Summary -->
<?php if (!empty($course_filter) && !empty($date_filter)): 
    $total_students = $conn->query("SELECT COUNT(*) as count FROM student_courses sc 
                                   JOIN students s ON sc.student_id = s.id 
                                   WHERE sc.course_id = $course_filter AND s.status = 'active'")->fetch_assoc()['count'];
    $present_count = $conn->query("SELECT COUNT(*) as count FROM attendance a 
                                  JOIN students s ON a.student_id = s.id 
                                  WHERE a.course_id = $course_filter AND a.attendance_date = '$date_filter' 
                                  AND a.status = 'present' AND s.status = 'active'")->fetch_assoc()['count'];
    $absent_count = $total_students - $present_count;
    $attendance_percentage = $total_students > 0 ? ($present_count / $total_students) * 100 : 0;
?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?php echo $total_students; ?></h3>
                <p class="mb-0">Total Students</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo $present_count; ?></h3>
                <p class="mb-0">Present</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3><?php echo $absent_count; ?></h3>
                <p class="mb-0">Absent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo number_format($attendance_percentage, 1); ?>%</h3>
                <p class="mb-0">Attendance Rate</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Attendance Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="attendanceTable">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($attendance->num_rows > 0): ?>
                        <?php while($record = $attendance->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['course_code'] . ' - ' . $record['course_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['attendance_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $record['status'] == 'present' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($record['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger delete-btn" 
                                        data-id="<?php echo $record['id']; ?>" 
                                        data-student="<?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>"
                                        data-date="<?php echo date('M d, Y', strtotime($record['attendance_date'])); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No attendance records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mark Attendance Modal -->
<div class="modal fade" id="markAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="mark_attendance">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Course *</label>
                            <select name="course_id" class="form-select" id="attendance_course" required>
                                <option value="">Select Course</option>
                                <?php 
                                $course_list = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");
                                while($course = $course_list->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date *</label>
                            <input type="date" name="attendance_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" id="attendanceTableModal">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                <tr>
                                    <td colspan="4" class="text-center">Please select a course first</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitAttendance" disabled>Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Attendance Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the attendance record for <strong id="delete_student"></strong> on <strong id="delete_date"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attendance Report Modal -->
<div class="modal fade" id="attendanceReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Attendance Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <select id="report_course" class="form-select">
                            <option value="">Select Course</option>
                            <?php 
                            $course_list = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");
                            while($course = $course_list->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" id="report_from_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    <div class="col-md-4">
                        <input type="date" id="report_to_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="reportTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Total Classes</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Attendance %</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <tr>
                                <td colspan="5" class="text-center">Select a course and date range to view report</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>