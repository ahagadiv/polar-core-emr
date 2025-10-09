<?php
/**
 * POLAR Healthcare Session Keep-Alive Handler
 * Prevents session timeouts during active use
 */

// Set JSON response header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Include OpenEMR globals
require_once(__DIR__ . '/../../globals.php');

// Check if user is logged in
if (empty($_SESSION['authUser'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Handle keep-alive request
if (isset($_POST['action']) && $_POST['action'] === 'keepalive') {
    try {
        // Update session tracker to prevent timeout
        if (!empty($_SESSION['session_database_uuid'])) {
            // Use the SessionTracker class if available
            if (class_exists('OpenEMR\Common\Session\SessionTracker')) {
                \OpenEMR\Common\Session\SessionTracker::updateSessionExpiration();
            } else {
                // Fallback: direct database update
                sqlStatementNoLog("UPDATE `session_tracker` SET `last_updated` = NOW() WHERE `uuid` = ?", [$_SESSION['session_database_uuid']]);
            }
            
            // POLAR Healthcare: Don't regenerate session ID in keep-alive to prevent session loss
            
            echo json_encode([
                'success' => true,
                'timestamp' => time(),
                'user' => $_SESSION['authUser'],
                'message' => 'Session keep-alive successful'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No session UUID found'
            ]);
        }
    } catch (Exception $e) {
        error_log('POLAR Healthcare: Session keep-alive error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Keep-alive failed'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
?>
