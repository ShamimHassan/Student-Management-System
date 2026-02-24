<?php
$page_title = "Parents Management";
require_once '../includes/header.php';
requireLogin();

// Handle form submissions
$message = '';
$message_type = '';

// Add, edit, delete logic for parents would go here
// For now, just a placeholder page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="fas fa-user-friends me-2"></i>Parents Management</h1>
        <div class="alert alert-info mt-4">Parent management functionality coming soon.</div>
    </div>
</body>
</html>
