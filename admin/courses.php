<?php
$page_title = "Courses Management";
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

$message = '';
$message_type = '';
$is_ajax = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1');

/* ===============================
   HANDLE FORM SUBMISSION
=================================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    /* ---------- ADD COURSE ---------- */
    if ($_POST['action'] === 'add') {
        $course_code = trim($_POST['course_code']);
        $course_name = trim($_POST['course_name']);
        $description = trim($_POST['description']);
        $credits = intval($_POST['credits']);
        $fee = floatval($_POST['fee']);

        // Duplicate Check
        $check = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $check->bind_param("s", $course_code);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Course code already exists!";
            $message_type = "danger";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $message
                ]);
                exit();
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO courses 
                (course_code, course_name, description, credits, fee) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssid", $course_code, $course_name, $description, $credits, $fee);

            if ($stmt->execute()) {
                $message = "Course added successfully!";
                $message_type = "success";
                if ($is_ajax) {
                    $id = $stmt->insert_id;
                    $created_at = date('Y-m-d H:i:s');
                    $created_at_formatted = date('M d, Y');
                    $course = [
                        'id' => $id,
                        'course_code' => $course_code,
                        'course_name' => $course_name,
                        'description' => $description,
                        'credits' => $credits,
                        'fee' => $fee,
                        'created_at' => $created_at,
                        'created_at_formatted' => $created_at_formatted
                    ];
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message,
                        'course' => $course
                    ]);
                    exit();
                }
            } else {
                $message = "Error adding course!";
                $message_type = "danger";
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $message
                    ]);
                    exit();
                }
            }
        }
    }

    /* ---------- EDIT COURSE ---------- */
    if ($_POST['action'] === 'edit') {

        $id = intval($_POST['id']);
        $course_code = trim($_POST['course_code']);
        $course_name = trim($_POST['course_name']);
        $description = trim($_POST['description']);
        $credits = intval($_POST['credits']);
        $fee = floatval($_POST['fee']);

        $check = $conn->prepare("SELECT id FROM courses WHERE course_code = ? AND id != ?");
        $check->bind_param("si", $course_code, $id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Course code already exists!";
            $message_type = "danger";
        } else {
            $stmt = $conn->prepare("UPDATE courses SET 
                course_code = ?, 
                course_name = ?, 
                description = ?, 
                credits = ?, 
                fee = ?
                WHERE id = ?");
            $stmt->bind_param("sssidi", $course_code, $course_name, $description, $credits, $fee, $id);

            if ($stmt->execute()) {
                $message = "Course updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error updating course!";
                $message_type = "danger";
            }
        }
    }

    /* ---------- DELETE COURSE ---------- */
    if ($_POST['action'] === 'delete') {

        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = "Course deleted successfully!";
            $message_type = "success";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $message]);
                exit();
            }
        } else {
            $message = "Error deleting course!";
            $message_type = "danger";
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $message]);
                exit();
            }
        }
    }
}

require_once '../includes/header.php';

/* ===============================
   FETCH COURSES
=================================*/
$sql = "SELECT c.*, COUNT(sc.student_id) as student_count
        FROM courses c
        LEFT JOIN student_courses sc ON c.id = sc.course_id
        GROUP BY c.id
        ORDER BY c.created_at DESC";

$result = $conn->query($sql);
?>

<div class="container mt-4">

    <div class="d-flex justify-content-between mb-3">
        <h2 class="text-primary">Courses Management</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
            Add Course
        </button>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Credits</th>
                        <th>Fee</th>
                        <th>Students</th>
                        <th>Created</th>
                        <th width="150">Action</th>
                    </tr>
                </thead>
                <tbody>

                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><?php echo number_format($row['fee'],2); ?></td>
                            <td><?php echo $row['student_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <button 
                                    class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editCourseModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-code="<?php echo htmlspecialchars($row['course_code']); ?>"
                                    data-name="<?php echo htmlspecialchars($row['course_name']); ?>"
                                    data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                                    data-credits="<?php echo $row['credits']; ?>"
                                    data-fee="<?php echo $row['fee']; ?>"
                                >
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No courses found</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>


<!-- ADD COURSE MODAL -->
<div class="modal fade" id="addCourseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label>Course Code</label>
                        <input type="text" name="course_code" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Course Name</label>
                        <input type="text" name="course_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Credits</label>
                            <input type="number" name="credits" class="form-control" value="3" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Fee</label>
                            <input type="number" name="fee" class="form-control" value="0" step="0.01">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT COURSE MODAL -->
<div class="modal fade" id="editCourseModal">
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
                    <div class="mb-3">
                        <label>Course Code</label>
                        <input type="text" name="course_code" id="edit_course_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Course Name</label>
                        <input type="text" name="course_name" id="edit_course_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Credits</label>
                            <input type="number" name="credits" id="edit_credits" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Fee</label>
                            <input type="number" name="fee" id="edit_fee" class="form-control" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
function confirmDelete(id) {
    if(confirm("Are you sure to delete this course?")) {
        const form = document.createElement("form");
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

var editModal = document.getElementById('editCourseModal');
editModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('edit_id').value = button.getAttribute('data-id');
    document.getElementById('edit_course_code').value = button.getAttribute('data-code');
    document.getElementById('edit_course_name').value = button.getAttribute('data-name');
    document.getElementById('edit_description').value = button.getAttribute('data-desc') || '';
    document.getElementById('edit_credits').value = button.getAttribute('data-credits');
    document.getElementById('edit_fee').value = button.getAttribute('data-fee');
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            if (confirm("Are you sure to delete this course?")) {
                fetch('courses.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'delete', id: id, ajax: '1' })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Delete failed');
                    }
                })
                .catch(function() { alert('Delete failed'); });
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
