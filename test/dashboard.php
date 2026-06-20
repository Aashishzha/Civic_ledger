<?php
/**
 * CivicLedger - Dashboard Router
 */
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
$role = $user['role'];

switch ($role) {
    case 'citizen':
        header("Location: citizen_dashboard.php");
        break;
    case 'student':
        header("Location: student_dashboard.php");
        break;
    case 'sponsor':
        header("Location: sponsor_dashboard.php");
        break;
    case 'mentor':
        header("Location: mentor_dashboard.php");
        break;
    case 'admin':
    case 'moderator':
        header("Location: admin_dashboard.php");
        break;
    default:
        setFlash('error', 'Invalid user role. Please contact support.');
        header("Location: logout.php");
}
exit;