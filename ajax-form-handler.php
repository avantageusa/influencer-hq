<?php
// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Log the data to debug.log
if (function_exists('error_log')) {
    error_log('AJAX FORM DATA: ' . print_r($_POST, true));
}

// Respond with success
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Form data logged.']);
exit;