<?php
/**
 * Get Templates API
 * Fetches templates from database and generates screenshots
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database configuration
$db_host = 'localhost';
$db_name = 'template';
$db_user = 'root';
$db_pass = '';

try {
    // Connect to database
    $connection = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass
    );
    
    // Set error mode to exceptions for better error handling
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch all templates from database
    $query = "SELECT id, name, link, screenshot FROM templates ORDER BY id ASC";
    $statement = $connection->query($query);
    $templates = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    // Modify the image URL to point to the correct local screenshot file
    foreach ($templates as &$template) {
        if ($template['screenshot']) {
            $template['image_url'] = '/Template/screenshots/' . $template['screenshot'];
        } else {
            // Generate screenshot dynamically from the link field
            $capture_url = 'http://localhost/Template/PHP file/capture_screenshot.php?url=' . urlencode($template['link']);
            $response = file_get_contents($capture_url);
            $result = json_decode($response, true);

            // Correctly update the screenshot field with the filename after capturing the screenshot
            if ($result['success'] && isset($result['filename'])) {
                $template['image_url'] = '/Template/screenshots/' . $result['filename'];

                // Update the database with the new screenshot filename
                $update_query = "UPDATE templates SET screenshot = :screenshot WHERE id = :id";
                $update_stmt = $connection->prepare($update_query);
                $update_stmt->execute([
                    ':screenshot' => $result['filename'],
                    ':id' => $template['id']
                ]);
            } else {
                // Fallback to placeholder if screenshot capture fails
                $template['image_url'] = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22600%22 height=%22400%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22600%22 height=%22400%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2224%22 fill=%22%23999%22%3ENo screenshot%3C/text%3E%3C/svg%3E';
            }
        }
    }
    
    // Return success response with template data
    echo json_encode([
        'success' => true,
        'data' => $templates,
        'count' => count($templates)
    ]);
    
} catch (PDOException $error) {
    // Return error response if something goes wrong
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $error->getMessage()
    ]);
}
?>
