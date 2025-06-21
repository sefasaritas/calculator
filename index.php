<?php
// Check if user is already logged in
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Redirect to login page
    header('Location: /login.php');
    exit();
}

// If user is logged in, show the main content or redirect to dashboard
header('Location: /dashboard.php'); // or wherever you want logged-in users to go
exit();
?>