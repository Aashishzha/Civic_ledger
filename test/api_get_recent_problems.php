<?php
/**
 * CivicLedger - API: Recent Problems
 */
header('Content-Type: application/json');
require_once 'config.php';

echo json_encode(getRecentProblems(6));