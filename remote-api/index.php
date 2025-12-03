<?php
/**
 * Attendance Sync API Endpoint
 * PHP 5.2+ Compatible
 *
 * This is a simple API endpoint that receives attendance data
 * from the console application and logs it to a file.
 */

// Set timezone to Brisbane
date_default_timezone_set('Australia/Brisbane');

// Configuration
define('LOG_DIR', dirname(__FILE__) . '/logs');
define('LOG_FILE', LOG_DIR . '/attendance-' . date('Y-m-d') . '.log');
define('API_KEY', '57f6a5c35acc14c5111cad9dda7c8ce4e10875db542653dcf08be15042ea4414'); // Change this to match your .env ATTENDANCE_API_KEY

// Database Configuration
define('DB_HOST', 'sunengim.c8hksybtlsnc.ap-southeast-2.rds.amazonaws.com');
define('DB_USER', 'imsunengdb');
define('DB_PASS', 'e1vA8W!$IPB^#XXH9E7UjUIj^jG*Ku0m');
define('DB_NAME', 'imsuneng_rds…');
define('DB_TABLE', 'attendance_records');
// define('IM_DB_HOST','sunengim.c8hksybtlsnc.ap-southeast-2.rds.amazonaws.com');
// define('IM_DB_USERNAME','imsunengdb');
// define('IM_DB_DATABASE','imsuneng_rds');
// define('IM_DB_PASSWORD','e1vA8W!$IPB^#XXH9E7UjUIj^jG*Ku0m');

// Create logs directory if it doesn't exist
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Database connection
$db = null;
function getDbConnection() {
    global $db;
    if ($db === null) {
        $db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$db) {
            logData('ERROR: Database connection failed', array(
                'error' => mysqli_connect_error()
            ));
            sendJsonResponse(array(
                'success' => false,
                'message' => 'Database connection failed'
            ), 500);
        }
        // Set charset to UTF-8
        mysqli_set_charset($db, 'utf8');
    }
    return $db;
}

// Helper function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function to log data
function logData($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";

    if ($data !== null) {
        $logMessage .= "\n" . print_r($data, true);
    }

    $logMessage .= "\n" . str_repeat('-', 80) . "\n";

    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
}

// Helper function to get request headers
function getRequestHeaders() {
    if (function_exists('getallheaders')) {
        return getallheaders();
    }

    // Fallback for servers that don't have getallheaders()
    $headers = array();
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}

// Helper function to check API key
function checkApiKey() {
    $headers = getRequestHeaders();

    // Check Authorization header
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (empty($authHeader)) {
        logData('ERROR: Missing Authorization header', array(
            'headers' => $headers,
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI']
        ));
        sendJsonResponse(array(
            'success' => false,
            'message' => 'Missing Authorization header'
        ), 401);
    }

    // Extract token from "Bearer TOKEN" format
    $token = '';
    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    }

    if ($token !== API_KEY) {
        logData('ERROR: Invalid API key', array(
            'provided_token' => $token,
            'expected_token' => API_KEY
        ));
        sendJsonResponse(array(
            'success' => false,
            'message' => 'Invalid API key'
        ), 401);
    }
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Route the request based on method only
if ($method === 'GET') {
    // Health check endpoint - no auth required
    logData('Health check request');
    sendJsonResponse(array(
        'status' => 'ok',
        'timestamp' => date('c'),
        'message' => 'API is running'
    ));

} elseif ($method === 'POST') {
    // Attendance data endpoint - requires authentication
    checkApiKey();

    // Get request body
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if ($data === null) {
        logData('ERROR: Invalid JSON received', array(
            'raw_input' => $rawInput,
            'json_error' => 'JSON decode failed'
        ));
        sendJsonResponse(array(
            'success' => false,
            'message' => 'Invalid JSON data'
        ), 400);
    }

    // Log the received data
    $records = isset($data['records']) ? $data['records'] : array();
    $deviceInfo = isset($data['device_info']) ? $data['device_info'] : array();

    logData('ATTENDANCE DATA RECEIVED', array(
        'total_records' => count($records),
        'device_info' => $deviceInfo,
        'records' => $records,
        'raw_data' => $data
    ));

    // Save records to database
    $db = getDbConnection();
    $savedCount = 0;
    $failedCount = 0;
    $errors = array();

    foreach ($records as $record) {
        // Extract fields
        $userId = isset($record['user_id']) ? $record['user_id'] : '';
        $timestamp = isset($record['timestamp']) ? $record['timestamp'] : date('Y-m-d H:i:s');
        $verifyType = isset($record['verify_type']) ? $record['verify_type'] : '';
        $status = isset($record['status']) ? $record['status'] : '';
        $rawTimestamp = isset($record['raw_timestamp']) ? intval($record['raw_timestamp']) : 0;

        // Device info
        $deviceType = isset($deviceInfo['type']) ? $deviceInfo['type'] : '';
        $deviceIp = isset($deviceInfo['ip']) ? $deviceInfo['ip'] : '';
        $syncedAt = isset($deviceInfo['synced_at']) ? $deviceInfo['synced_at'] : date('Y-m-d H:i:s');

        // Build INSERT query
        $query = sprintf(
            "INSERT INTO %s (user_id, timestamp, verify_type, status, raw_timestamp, device_type, device_ip, synced_at, created_at) VALUES ('%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s')",
            DB_TABLE,
            mysqli_real_escape_string($db, $userId),
            mysqli_real_escape_string($db, $timestamp),
            mysqli_real_escape_string($db, $verifyType),
            mysqli_real_escape_string($db, $status),
            $rawTimestamp,
            mysqli_real_escape_string($db, $deviceType),
            mysqli_real_escape_string($db, $deviceIp),
            mysqli_real_escape_string($db, $syncedAt),
            date('Y-m-d H:i:s')
        );

        if (mysqli_query($db, $query)) {
            $savedCount++;
        } else {
            $failedCount++;
            $errors[] = array(
                'user_id' => $userId,
                'error' => mysqli_error($db)
            );
        }
    }

    // Log the result
    logData('DATABASE SAVE RESULT', array(
        'total_records' => count($records),
        'saved' => $savedCount,
        'failed' => $failedCount,
        'errors' => $errors
    ));

    // Send success response
    sendJsonResponse(array(
        'success' => $failedCount === 0,
        'message' => $savedCount . ' records saved successfully' . ($failedCount > 0 ? ', ' . $failedCount . ' failed' : ''),
        'records_received' => count($records),
        'records_saved' => $savedCount,
        'records_failed' => $failedCount,
        'timestamp' => date('c')
    ));

} else {
    // Unsupported method
    logData('ERROR: Unsupported HTTP method', array(
        'method' => $method,
        'uri' => $_SERVER['REQUEST_URI']
    ));
    sendJsonResponse(array(
        'success' => false,
        'message' => 'Method not allowed'
    ), 405);
}
