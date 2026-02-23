<?php
$page_title = "Results Management";
require_once '../includes/header.php';
requireLogin();

// Function to calculate grade based on percentage (5.0 scale)
function calculateGrade($percentage) {
    if ($percentage >= 80) return '5.00 (A+)';
    if ($percentage >= 70) return '4.00 (A)';
    if ($percentage >= 60) return '3.50 (A-)';
    if ($percentage >= 50) return '3.00 (B)';
    if ($percentage >= 40) return '2.00 (C)';
    if ($percentage >= 33) return '1.00 (D)';
    return '0.00 (F)';
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $student_id = $_POST['student_id'];
                $course_id = $_POST['course_id'];
                $exam_name = trim($_POST['exam_name']);
                $marks_obtained = $_POST['marks_obtained'];
                $total_marks = $_POST['total_marks'];
                $exam_date = $_POST['exam_date'];
                
                // Calculate percentage and grade
                $percentage = ($marks_obtained / $total_marks) * 100;
                $grade = calculateGrade($percentage);
                
                // Check if result already exists
                $check = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND course_id = ? AND exam_name = ?");
                $check->bind_param("iis", $student_id, $course_id, $exam_name);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $message = "Result for this exam already exists!";
                    $message_type = "danger";
                } else {
                    $stmt = $conn->prepare("INSERT INTO results (student_id, course_id, exam_name, marks_obtained, total_marks, grade, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiddsss", $student_id, $course_id, $exam_name, $marks_obtained, $total_marks, $grade, $exam_date);
                    
                    if ($stmt->execute()) {
                        $message = "Result added successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error adding result!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $exam_name = trim($_POST['exam_name']);
                $marks_obtained = $_POST['marks_obtained'];
                $total_marks = $_POST['total_marks'];
                $exam_date = $_POST['exam_date'];
                
                // Calculate percentage and grade
                $percentage = ($marks_obtained / $total_marks) * 100;
                $grade = calculateGrade($percentage);
                
                $stmt = $conn->prepare("UPDATE results SET exam_name = ?, marks_obtained = ?, total_marks = ?, grade = ?, exam_date = ? WHERE id = ?");
                $stmt->bind_param("sddssi", $exam_name, $marks_obtained, $total_marks, $grade, $exam_date, $id);
                
                if ($stmt->execute()) {
                    $message = "Result updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating result!";
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM results WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Result deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting result!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get data for dropdowns
$students = $conn->query("SELECT id, student_id, first_name, last_name FROM students WHERE status = 'active' ORDER BY first_name, last_name");
$courses = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");

// Get results data
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$student_filter = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$course_filter = isset($_GET['course_id']) ? $_GET['course_id'] : '';

$sql = "SELECT r.*, s.student_id, s.first_name, s.last_name, c.course_code, c.course_name 
        FROM results r 
        JOIN students s ON r.student_id = s.id 
        JOIN courses c ON r.course_id = c.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR c.course_name LIKE ? OR r.exam_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($student_filter)) {
    $sql .= " AND r.student_id = ?";
    $params[] = $student_filter;
    $types .= "i";
}

if (!empty($course_filter)) {
    $sql .= " AND r.course_id = ?";
    $params[] = $course_filter;
    $types .= "i";
}

$sql .= " ORDER BY r.exam_date DESC, s.first_name, s.last_name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-chart-line me-2"></i>Results Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addResultModal">
                <i class="fas fa-plus me-1"></i>Add Result
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

<!-- Search and Filters -->
<div class="row mb-4">
    <div class="col-md-4">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-4">
        <form method="GET">
            <select name="student_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Students</option>
                <?php while($student = $students->fetch_assoc()): ?>
                    <option value="<?php echo $student['id']; ?>" <?php echo $student_filter == $student['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                    </option>
                <?php 
                // Reset pointer for later use
                $students->data_seek(0);
                endwhile; 
                ?>
            </select>
            <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            <?php if (!empty($course_filter)): ?>
            <input type="hidden" name="course_id" value="<?php echo $course_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-4">
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
            <?php if (!empty($student_filter)): ?>
            <input type="hidden" name="student_id" value="<?php echo $student_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="resultsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Exam</th>
                        <th>Marks</th>
                        <th>Percentage</th>
                        <th>Grade</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($results->num_rows > 0): ?>
                        <?php while($result = $results->fetch_assoc()): 
                            $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                            $grade_class = '';
                            switch($result['grade']) {
                                case '5.00 (A+)': case '4.00 (A)': $grade_class = 'success'; break;
                                case '3.50 (A-)': case '3.00 (B)': $grade_class = 'primary'; break;
                                case '2.00 (C)': $grade_class = 'warning'; break;
                                case '1.00 (D)': $grade_class = 'info'; break;
                                case '0.00 (F)': $grade_class = 'danger'; break;
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($result['student_id']); ?></strong><br>
                                <?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($result['course_code']); ?></strong><br>
                                <?php echo htmlspecialchars($result['course_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($result['exam_name']); ?></td>
                            <td><?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?></td>
                            <td><?php echo number_format($percentage, 1); ?>%</td>
                            <td><span class="badge bg-<?php echo $grade_class; ?>"><?php echo $result['grade']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($result['exam_date'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                            data-result='<?php echo json_encode($result); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                            data-id="<?php echo $result['id']; ?>" 
                                            data-exam="<?php echo htmlspecialchars($result['exam_name']); ?>"
                                            data-student="<?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No results found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Result Modal -->
<div class="modal fade" id="addResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student *</label>
                            <select name="student_id" class="form-select" required>
                                <option value="">Select Student</option>
                                <?php 
                                $students->data_seek(0);
                                while($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course *</label>
                            <select name="course_id" class="form-select" required>
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
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exam Name *</label>
                            <input type="text" name="exam_name" class="form-control" placeholder="e.g., Midterm, Final" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exam Date *</label>
                            <input type="date" name="exam_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marks Obtained *</label>
                            <input type="number" name="marks_obtained" id="marks_obtained" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Marks *</label>
                            <input type="number" name="total_marks" id="total_marks" class="form-control" min="1" value="100" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percentage</label>
                        <input type="text" id="percentage" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Result</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Result Modal -->
<div class="modal fade" id="editResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exam Name *</label>
                            <input type="text" name="exam_name" id="edit_exam_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Exam Date *</label>
                            <input type="date" name="exam_date" id="edit_exam_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marks Obtained *</label>
                            <input type="number" name="marks_obtained" id="edit_marks_obtained" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Marks *</label>
                            <input type="number" name="total_marks" id="edit_total_marks" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Percentage</label>
                        <input type="text" id="edit_percentage" class="form-control" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Result</button>
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
                    <h5 class="modal-title">Delete Result</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the result for <strong id="delete_exam"></strong> of <strong id="delete_student"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>