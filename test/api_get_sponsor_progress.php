<?php
/**
 * CivicLedger - API: Sponsor Progress Data
 */
header('Content-Type: application/json');
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user = getCurrentUser();

if ($user['role'] !== 'sponsor' && $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

echo json_encode(['success' => true, 'data' => getSponsorProgress($user['id'])]);