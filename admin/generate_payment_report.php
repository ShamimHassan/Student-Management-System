<?php
require_once '../includes/config.php';
requireLogin();

$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';

if ($from_date && $to_date) {
    $sql = "SELECT p.*, s.student_id, s.first_name, s.last_name, c.course_code, c.course_name,
            (SELECT SUM(amount) FROM payments WHERE student_id = p.student_id AND course_id = p.course_id AND status = 'paid') as paid_amount,
            c.fee as total_fee
            FROM payments p 
            JOIN students s ON p.student_id = s.id 
            JOIN courses c ON p.course_id = c.id 
            WHERE p.payment_date BETWEEN ? AND ?
            GROUP BY p.student_id, p.course_id
            ORDER BY s.first_name, s.last_name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from_date, $to_date);
    $stmt->execute();
    $payments = $stmt->get_result();
    
    if ($payments->num_rows > 0) {
        while($payment = $payments->fetch_assoc()) {
            $paid_amount = $payment['paid_amount'] ?? 0;
            $due_amount = $payment['total_fee'] - $paid_amount;
            $status = $due_amount <= 0 ? 'Paid' : 'Due';
            $status_class = $due_amount <= 0 ? 'success' : 'warning';
            
            echo '<tr>';
            echo '<td>' . htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name'] . ' (' . $payment['student_id'] . ')') . '</td>';
            echo '<td>' . htmlspecialchars($payment['course_code'] . ' - ' . $payment['course_name']) . '</td>';
            echo '<td>৳' . number_format($payment['total_fee'], 2) . '</td>';
            echo '<td>৳' . number_format($paid_amount, 2) . '</td>';
            echo '<td>৳' . number_format($due_amount, 2) . '</td>';
            echo '<td><span class="badge bg-' . $status_class . '">' . $status . '</span></td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="6" class="text-center">No payment records found for the selected period</td></tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center">Please select valid date range</td></tr>';
}
?>