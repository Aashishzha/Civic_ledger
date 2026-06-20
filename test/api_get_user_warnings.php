<?php
/**
 * CivicLedger - Get User Warnings API
 */
require_once 'config.php';

$userId = intval($_GET['user_id'] ?? 0);
$warnings = getUserWarnings($userId);

echo json_encode($warnings);