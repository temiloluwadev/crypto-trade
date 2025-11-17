<?php
require_once 'includes/auth.php';
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
} else {
    header('Location: login.php');
    exit();
}
?>