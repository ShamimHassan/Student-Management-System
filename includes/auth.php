<?php
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check user role
function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

// Check if user is admin
function isAdmin() {
    return getUserRole() === 'admin';
}

// Check if user is student
function isStudent() {
    return getUserRole() === 'student';
}

// Redirect to login if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "login.php");
        exit();
    }
}

// Redirect based on role
function redirectBasedOnRole() {
    $role = getUserRole();
    switch($role) {
        case 'admin':
            header("Location: " . BASE_URL . "admin/dashboard.php");
            break;
        case 'teacher':
            header("Location: " . BASE_URL . "teacher/dashboard.php");
            break;
        case 'parent':
            header("Location: " . BASE_URL . "parent/dashboard.php");
            break;
        case 'student':
            header("Location: " . BASE_URL . "student/dashboard.php");
            break;
        default:
            header("Location: " . BASE_URL . "login.php");
    }
    exit();
}

// Get logged in user details
function getUserDetails($conn) {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT a.*, s.student_id, s.first_name, s.last_name, s.email as student_email 
                FROM admins a 
                LEFT JOIN students s ON a.student_id = s.id 
                WHERE a.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}

// Login function
function login($conn, $username, $password, $role) {
    $sql = "SELECT a.id, a.username, a.password, a.role, a.student_id 
            FROM admins a 
            WHERE a.username = ? AND a.role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['student_id'] = $user['student_id'];
            return true;
        }
    }
    return false;
}

// Logout function
function logout() {
    session_destroy();
    header("Location: " . BASE_URL . "login.php");
    exit();
}
?>