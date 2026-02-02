<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect based on login status and role
if (isLoggedIn()) {
    redirectBasedOnRole();
} else {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
?>