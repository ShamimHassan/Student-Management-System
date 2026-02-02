<?php
$page_title = "Courses Management";
require_once '../includes/header.php';
requireLogin();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $course_code = trim($_POST['course_code']);
                $course_name = trim($_POST['course_name']);
                $description = trim($_POST['description']);
                $credits = $_POST['credits'];
                $fee = $_POST['fee'];
                
                // Check if course code already exists
                $check = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
                $check->bind_param("s", $course_code);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $message = "Course code already exists!";
                    $message_type = "danger";
                } else {
                    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, description, credits, fee) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssii", $course_code, $course_name, $description, $credits, $fee);
                    
                    if ($stmt->execute()) {
                        $message = "Course added successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error adding course!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $course_code = trim($_POST['course_code']);
                $course_name = trim($_POST['course_name']);
                $description = trim($_POST['description']);
                $credits = $_POST['credits'];
                $fee = $_POST['fee'];
                
                // Check if course code already exists for other courses
                $check = $conn->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
                $check->bind_param("si", $course_code, $id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $message = "Course code already exists!";
                    $message_type = "danger";
                } else {
                    $stmt = $conn->prepare("UPDATE courses SET course_code = ?, course_name = ?, description = ?, credits = ?, fee = ? WHERE id = ?");
                    $stmt->bind_param("sssiii", $course_code, $course_name, $description, $credits, $fee, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Course updated successfully!";
                        $message_type = "success";
                    } else {
                        $message = "Error updating course!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Course deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting course!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get courses data
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT c.*, COUNT(sc.student_id) as student_count FROM courses c 
        LEFT JOIN student_courses sc ON c.id = sc.course_id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (course_code LIKE ? OR course_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

$sql .= " GROUP BY c.id ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$courses = $stmt->get_result();
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-book me-2"></i>Courses Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="fas fa-plus me-1"></i>Add Course
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

<!-- Search -->
<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search by code or name..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<!-- Courses Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="coursesTable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Credits</th>
                        <th>Fee ($)</th>
                        <th>Students</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($courses->num_rows > 0): ?>
                        <?php while($course = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($course['description'], 0, 50)) . (strlen($course['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $course['credits']; ?></td>
                            <td><?php echo number_format($course['fee'], 2); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $course['student_count']; ?> students</span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                            data-course='<?php echo json_encode($course); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                            data-id="<?php echo $course['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($course['course_name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No courses found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Code *</label>
                            <input type="text" name="course_code" class="form-control" required>
                            <small class="text-muted">Unique course identifier</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Name *</label>
                            <input type="text" name="course_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credits *</label>
                            <input type="number" name="credits" class="form-control" min="1" max="10" value="3" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Fee ($)</label>
                            <input type="number" name="fee" class="form-control" min="0" step="0.01" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Code *</label>
                            <input type="text" name="course_code" id="edit_course_code" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Name *</label>
                            <input type="text" name="course_name" id="edit_course_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credits *</label>
                            <input type="number" name="credits" id="edit_credits" class="form-control" min="1" max="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Fee ($)</label>
                            <input type="number" name="fee" id="edit_fee" class="form-control" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
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
                    <h5 class="modal-title">Delete Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete <strong id="delete_name"></strong>? This will also remove all related student enrollments, results, and attendance records.</p>
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