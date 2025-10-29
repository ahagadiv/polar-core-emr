<?php
/**
 * POLAR Healthcare - Update Appointment Status
 * Handles AJAX requests to update appointment status from the popup modal
 */

// Ensure we're in OpenEMR context
$ignoreAuth = false;
require_once("../../globals.php");
require_once("$srcdir/sql.inc");
require_once("$srcdir/formatting.inc.php");

// Set JSON header
header('Content-Type: application/json');

// Check if user is authenticated
if (!$_SESSION['authUserID']) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['appointmentId']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$appointmentId = intval($input['appointmentId']);
$newStatus = $input['status'];

// Validate appointment ID
if ($appointmentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
    exit;
}

// Validate status
$validStatuses = ['S', 'V', 'x', '?', '@', '~', '<', '>', '!', '#', '$', '%', '^', 'STAT'];
if (!in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Get current appointment data
    $appointmentQuery = "SELECT pc_eid, pc_pid, pc_eventDate, pc_startTime, pc_apptstatus, pc_title, pc_hometext, pc_facility, pc_billing_location, pc_room 
                        FROM openemr_postcalendar_events 
                        WHERE pc_eid = ? AND pc_recurrtype = 0";
    $appointmentData = sqlQuery($appointmentQuery, [$appointmentId]);
    
    if (!$appointmentData) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    // Check if user has permission to modify this appointment
    // For now, we'll allow modification if user is authenticated
    // In production, you might want to add more specific permission checks
    
    // Update appointment status
    $updateQuery = "UPDATE openemr_postcalendar_events 
                   SET pc_apptstatus = ?, pc_time = NOW() 
                   WHERE pc_eid = ?";
    
    $result = sqlStatement($updateQuery, [$newStatus, $appointmentId]);
    
    if ($result === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to update appointment status']);
        exit;
    }
    
    // If this is a patient appointment, update patient tracker if needed
    if ($appointmentData['pc_pid']) {
        // Check if patient tracker is enabled
        if (!empty($GLOBALS['gbl_auto_update_appt_status'])) {
            require_once("$srcdir/patient_tracker.inc.php");
            
            // Update patient tracker status
            $trackerService = new PatientTrackerService();
            $trackerService->manage_tracker_status(
                $appointmentData['pc_eventDate'],
                $appointmentData['pc_startTime'],
                $appointmentId,
                $appointmentData['pc_pid'],
                $_SESSION['authUserID'],
                $newStatus,
                $appointmentData['pc_room'],
                '' // encounter ID - empty for now
            );
        }
    }
    
    // Log the status change
    $logMessage = "Appointment status changed from '{$appointmentData['pc_apptstatus']}' to '{$newStatus}' for appointment ID {$appointmentId}";
    error_log("POLAR Healthcare: " . $logMessage);
    
    // Get status display name for response
    $statusDisplayQuery = "SELECT title FROM list_options WHERE list_id = 'apptstat' AND option_id = ? AND activity = 1";
    $statusDisplay = sqlQuery($statusDisplayQuery, [$newStatus]);
    $statusName = $statusDisplay ? $statusDisplay['title'] : $newStatus;
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment status updated successfully',
        'newStatus' => $newStatus,
        'statusName' => $statusName,
        'appointmentId' => $appointmentId
    ]);
    
} catch (Exception $e) {
    error_log("POLAR Healthcare Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the appointment status']);
}
?>
