<?php
$page_title = "Payment Management";
require_once '../includes/header.php';
requireLogin();

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $student_id = $_POST['student_id'];
                $course_id = $_POST['course_id'];
                $amount = $_POST['amount'];
                $payment_date = $_POST['payment_date'];
                $payment_method = trim($_POST['payment_method']);
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("INSERT INTO payments (student_id, course_id, amount, payment_date, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiddss", $student_id, $course_id, $amount, $payment_date, $payment_method, $status);
                
                if ($stmt->execute()) {
                    $message = "Payment record added successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error adding payment record!";
                    $message_type = "danger";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $amount = $_POST['amount'];
                $payment_date = $_POST['payment_date'];
                $payment_method = trim($_POST['payment_method']);
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE payments SET amount = ?, payment_date = ?, payment_method = ?, status = ? WHERE id = ?");
                $stmt->bind_param("dsssi", $amount, $payment_date, $payment_method, $status, $id);
                
                if ($stmt->execute()) {
                    $message = "Payment record updated successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error updating payment record!";
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $message = "Payment record deleted successfully!";
                    $message_type = "success";
                } else {
                    $message = "Error deleting payment record!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get data for dropdowns
$students = $conn->query("SELECT id, student_id, first_name, last_name FROM students WHERE status = 'active' ORDER BY first_name, last_name");
$courses = $conn->query("SELECT id, course_code, course_name, fee FROM courses ORDER BY course_name");

// Get payments data
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$student_filter = isset($_GET['student_id']) ? $_GET['student_id'] : '';
$course_filter = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT p.*, s.student_id, s.first_name, s.last_name, c.course_code, c.course_name, c.fee 
        FROM payments p 
        JOIN students s ON p.student_id = s.id 
        JOIN courses c ON p.course_id = c.id 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR c.course_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($student_filter)) {
    $sql .= " AND p.student_id = ?";
    $params[] = $student_filter;
    $types .= "i";
}

if (!empty($course_filter)) {
    $sql .= " AND p.course_id = ?";
    $params[] = $course_filter;
    $types .= "i";
}

if (!empty($status_filter)) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY p.payment_date DESC, s.first_name, s.last_name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result();

// Calculate summary
$total_paid = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$total_due = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'due'")->fetch_assoc()['total'] ?? 0;
$overall_total = $total_paid + $total_due;
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-money-bill-wave me-2"></i>Payment Management</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                <i class="fas fa-plus me-1"></i>Add Payment
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

<!-- Payment Summary -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3>৳<?php echo number_format($total_paid, 2); ?></h3>
                <p class="mb-0">Total Paid</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h3>৳<?php echo number_format($total_due, 2); ?></h3>
                <p class="mb-0">Total Due</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3>৳<?php echo number_format($overall_total, 2); ?></h3>
                <p class="mb-0">Overall Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $overall_total > 0 ? number_format(($total_paid / $overall_total) * 100, 1) : 0; ?>%</h3>
                <p class="mb-0">Collection Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="row mb-4">
    <div class="col-md-3">
        <form method="GET" class="d-flex">
            <input type="text" name="search" class="form-control me-2" placeholder="Search..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-outline-secondary" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    <div class="col-md-2">
        <form method="GET">
            <select name="student_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Students</option>
                <?php 
                $student_list = $conn->query("SELECT id, student_id, first_name, last_name FROM students WHERE status = 'active' ORDER BY first_name, last_name");
                while($student = $student_list->fetch_assoc()): ?>
                    <option value="<?php echo $student['id']; ?>" <?php echo $student_filter == $student['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            <?php if (!empty($course_filter)): ?>
            <input type="hidden" name="course_id" value="<?php echo $course_filter; ?>">
            <?php endif; ?>
            <?php if (!empty($status_filter)): ?>
            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-2">
        <form method="GET">
            <select name="course_id" class="form-select" onchange="this.form.submit()">
                <option value="">All Courses</option>
                <?php 
                $course_list = $conn->query("SELECT id, course_code, course_name FROM courses ORDER BY course_name");
                while($course = $course_list->fetch_assoc()): ?>
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
            <?php if (!empty($status_filter)): ?>
            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-2">
        <form method="GET">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="paid" <?php echo $status_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="due" <?php echo $status_filter == 'due' ? 'selected' : ''; ?>>Due</option>
            </select>
            <?php if (!empty($search)): ?>
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
            <?php endif; ?>
            <?php if (!empty($student_filter)): ?>
            <input type="hidden" name="student_id" value="<?php echo $student_filter; ?>">
            <?php endif; ?>
            <?php if (!empty($course_filter)): ?>
            <input type="hidden" name="course_id" value="<?php echo $course_filter; ?>">
            <?php endif; ?>
        </form>
    </div>
    <div class="col-md-3">
        <div class="d-grid">
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#paymentReportModal">
                <i class="fas fa-file-invoice me-1"></i>Payment Report
            </button>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="paymentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Amount (Tk)</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments->num_rows > 0): ?>
                        <?php while($payment = $payments->fetch_assoc()): 
                            $status_class = $payment['status'] == 'paid' ? 'success' : 'warning';
                            $amount_class = $payment['amount'] >= $payment['fee'] ? 'text-success' : 'text-warning';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($payment['student_id']); ?></strong><br>
                                <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($payment['course_code']); ?></strong><br>
                                <?php echo htmlspecialchars($payment['course_name']); ?>
                            </td>
                            <td class="<?php echo $amount_class; ?>">
                                <strong>৳<?php echo number_format($payment['amount'], 2); ?></strong>
                                <?php if ($payment['amount'] < $payment['fee']): ?>
                                    <br><small class="text-muted">Due: ৳<?php echo number_format($payment['fee'] - $payment['amount'], 2); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo $payment['payment_method'] ? htmlspecialchars($payment['payment_method']) : 'N/A'; ?></td>
                            <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($payment['status']); ?></span></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                            data-payment='<?php echo json_encode($payment); ?>'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" 
                                            data-id="<?php echo $payment['id']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No payment records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Student *</label>
                            <select name="student_id" class="form-select" id="student_select" required>
                                <option value="">Select Student</option>
                                <?php 
                                $student_list = $conn->query("SELECT id, student_id, first_name, last_name FROM students WHERE status = 'active' ORDER BY first_name, last_name");
                                while($student = $student_list->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['student_id'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course *</label>
                            <select name="course_id" class="form-select" id="course_select" required>
                                <option value="">Select Course</option>
                                <?php 
                                $course_list = $conn->query("SELECT id, course_code, course_name, fee FROM courses ORDER BY course_name");
                                while($course = $course_list->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>" data-fee="<?php echo $course['fee']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (Tk) *</label>
                            <input type="number" name="amount" id="amount" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Course Fee</label>
                            <input type="text" id="course_fee" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="paid">Paid</option>
                                <option value="due">Due</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <input type="text" name="payment_method" class="form-control" placeholder="e.g., Cash, Credit Card, Bank Transfer">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount (Tk) *</label>
                            <input type="number" name="amount" id="edit_amount" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" id="edit_payment_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="paid">Paid</option>
                                <option value="due">Due</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Method</label>
                            <input type="text" name="payment_method" id="edit_payment_method" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Payment</button>
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
                    <h5 class="modal-title">Delete Payment Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this payment record?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Report Modal -->
<div class="modal fade" id="paymentReportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="date" id="report_from_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                    </div>
                    <div class="col-md-4">
                        <input type="date" id="report_to_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" id="generateReport">Generate Report</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="reportTable">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Due Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <tr>
                                <td colspan="6" class="text-center">Select date range and click Generate Report</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>