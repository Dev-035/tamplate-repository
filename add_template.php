<?php
// Set response headers for JSON API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration
$db_host = 'localhost';
$db_name = 'template';
$db_user = 'root';
$db_pass = '';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Only POST method is allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (!isset($input['name']) || !isset($input['link'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Template name and link are required'
    ]);
    exit;
}

$template_name = trim($input['name']);
$template_link = trim($input['link']);

// Basic validation
if (empty($template_name) || empty($template_link)) {
    echo json_encode([
        'success' => false,
        'error' => 'Template name and link cannot be empty'
    ]);
    exit;
}

// Validate URL format
if (!filter_var($template_link, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a valid URL'
    ]);
    exit;
}

try {
    // Connect to database
    $connection = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass
    );
    
    // Set error mode to exceptions
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if template with same name already exists
    $check_query = "SELECT COUNT(*) FROM templates WHERE name = :name";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bindParam(':name', $template_name);
    $check_stmt->execute();
    
    if ($check_stmt->fetchColumn() > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'A template with this name already exists'
        ]);
        exit;
    }
    
    // Capture screenshot first
    $screenshot_filename = null;
    try {
        $screenshot_url = urlencode($template_link);
        $capture_response = @file_get_contents("http://localhost/Template/PHP file/capture_screenshot.php?url=" . $screenshot_url);
        
        if ($capture_response) {
            $capture_data = json_decode($capture_response, true);
            if ($capture_data && $capture_data['success']) {
                $screenshot_filename = $capture_data['filename'];
            }
        }
    } catch (Exception $e) {
        // Screenshot capture failed, but continue without it
        error_log('Screenshot capture error: ' . $e->getMessage());
    }
    
    // Insert new template with screenshot filename
    $insert_query = "INSERT INTO templates (name, link, screenshot) VALUES (:name, :link, :screenshot)";
    $insert_stmt = $connection->prepare($insert_query);
    $insert_stmt->bindParam(':name', $template_name);
    $insert_stmt->bindParam(':link', $template_link);
    $insert_stmt->bindParam(':screenshot', $screenshot_filename);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Template added successfully',
            'template_id' => $connection->lastInsertId(),
            'screenshot_captured' => $screenshot_filename !== null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add template to database'
        ]);
    }
    
} catch (PDOException $error) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $error->getMessage()
    ]);
}
?>
