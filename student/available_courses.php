<?php
$page_title = "Available Courses";
require_once '../includes/config.php';
require_once '../includes/header.php';
requireLogin();

$message = '';
$message_type = '';

// Handle enrollment with payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id']) && isset($_POST['amount'])) {
    $student_id = $_SESSION['user_id'];
    $course_id = intval($_POST['course_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';

    // Check if already enrolled
    $check = $conn->prepare("SELECT id FROM student_courses WHERE student_id = ? AND course_id = ?");
    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();
    $result_check = $check->get_result();
    if ($result_check->num_rows > 0) {
        $message = 'You are already enrolled in this course.';
        $message_type = 'danger';
    } else {
        // Enroll student
        $stmt = $conn->prepare("INSERT INTO student_courses (student_id, course_id, enrollment_date, status) VALUES (?, ?, NOW(), 'enrolled')");
        $stmt->bind_param("ii", $student_id, $course_id);
        if ($stmt->execute()) {
            // Record payment
            $pay = $conn->prepare("INSERT INTO payments (student_id, course_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, NOW(), ?, 'paid')");
            $pay->bind_param("iids", $student_id, $course_id, $amount, $payment_method);
            if ($pay->execute()) {
                $message = 'Enrolled and payment successful!';
                $message_type = 'success';
            } else {
                $message = 'Enrollment successful, but payment failed.';
                $message_type = 'warning';
            }
        } else {
            $message = 'Enrollment failed.';
            $message_type = 'danger';
        }
    }
}

// Fetch all courses
$sql = "SELECT * FROM courses ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<div class="container mt-4">
    <h2 class="mb-4 text-primary">Available Courses</h2>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"> <?php echo $message; ?> </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Credits</th>
                        <th>Fee</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo $row['credits']; ?></td>
                            <td><?php echo number_format($row['fee'],2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="course_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="amount" value="<?php echo $row['fee']; ?>">
                                    <div class="input-group mb-2">
                                        <select name="payment_method" class="form-select form-select-sm" style="max-width:120px;">
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="bank">Bank</option>
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm ms-2">Enroll & Pay</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No courses found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
