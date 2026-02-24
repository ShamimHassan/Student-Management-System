<?php
$page_title = "Pay Course Fee";
require_once '../includes/config.php';
require_once '../includes/header.php';
requireLogin();

$message = '';
$message_type = '';

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id']) && isset($_POST['amount'])) {
    $student_id = $_SESSION['user_id'];
    $course_id = intval($_POST['course_id']);
    $amount = floatval($_POST['amount']);
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';

    // Check if already paid
    $check = $conn->prepare("SELECT id FROM payments WHERE student_id = ? AND course_id = ? AND status = 'paid'");
    $check->bind_param("ii", $student_id, $course_id);
    $check->execute();
    $result_check = $check->get_result();
    if ($result_check->num_rows > 0) {
        $message = 'You have already paid for this course.';
        $message_type = 'danger';
    } else {
        $pay = $conn->prepare("INSERT INTO payments (student_id, course_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, NOW(), ?, 'paid')");
        $pay->bind_param("iids", $student_id, $course_id, $amount, $payment_method);
        if ($pay->execute()) {
            $message = 'Payment successful!';
            $message_type = 'success';
        } else {
            $message = 'Payment failed.';
            $message_type = 'danger';
        }
    }
}

// Fetch student's courses
$sql = "SELECT c.id, c.course_code, c.course_name, c.fee FROM courses c
        JOIN student_courses sc ON c.id = sc.course_id
        WHERE sc.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$courses = $stmt->get_result();
?>
<div class="container mt-4">
    <h2 class="mb-4 text-primary">Pay Course Fee</h2>
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
                        <th>Fee</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($courses && $courses->num_rows > 0): ?>
                    <?php while($row = $courses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                            <td><?php echo number_format($row['fee'],2); ?></td>
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
                                        <button type="submit" class="btn btn-success btn-sm ms-2">Pay Fee</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No enrolled courses found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
