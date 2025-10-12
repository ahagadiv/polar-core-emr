<?php

/**
 * POLAR Clinical Workflow - Actions Handler
 * 
 * Handles AJAX requests for workflow actions like clock in/out, step completion, etc.
 */

require_once(__DIR__ . '/../../globals.php');
require_once(__DIR__ . '/../../library/PolarClinicalWorkflow.php');

header('Content-Type: application/json');

// Verify CSRF token
if (!CsrfUtils::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
$clinicianId = $_SESSION['authUser'];

try {
    switch ($action) {
        case 'create_workflow':
            $appointmentId = $_POST['appointment_id'] ?? '';
            $patientId = $_POST['patient_id'] ?? '';
            
            if (empty($appointmentId) || empty($patientId)) {
                throw new Exception('Missing required parameters');
            }
            
            // Get service line from appointment category
            $sql = "SELECT pc_catname FROM openemr_postcalendar_events pce
                    JOIN openemr_postcalendar_categories pc ON pce.pc_catid = pc.pc_catid
                    WHERE pce.pc_eid = ?";
            $result = sqlQuery($sql, [$appointmentId]);
            
            $serviceLine = 'vascular_access'; // Default
            if ($result && $result['pc_catname']) {
                $categoryName = strtolower($result['pc_catname']);
                if (strpos($categoryName, 'home health') !== false) {
                    $serviceLine = 'home_health';
                } elseif (strpos($categoryName, 'home infusion') !== false) {
                    $serviceLine = 'home_infusion';
                } elseif (strpos($categoryName, 'home dialysis') !== false) {
                    $serviceLine = 'home_dialysis';
                } elseif (strpos($categoryName, 'stat') !== false) {
                    $serviceLine = 'vascular_access_stat';
                }
            }
            
            $workflowId = PolarClinicalWorkflow::createWorkflow($appointmentId, $patientId, $clinicianId, $serviceLine);
            
            if ($workflowId) {
                echo json_encode(['success' => true, 'workflow_id' => $workflowId]);
            } else {
                throw new Exception('Failed to create workflow');
            }
            break;
            
        case 'clock_in':
            $workflowId = $_POST['workflow_id'] ?? '';
            $location = $_POST['location'] ?? null;
            
            if (empty($workflowId)) {
                throw new Exception('Missing workflow ID');
            }
            
            $result = PolarClinicalWorkflow::clockIn($workflowId, $location);
            
            if ($result !== false) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to clock in');
            }
            break;
            
        case 'clock_out':
            $workflowId = $_POST['workflow_id'] ?? '';
            $location = $_POST['location'] ?? null;
            
            if (empty($workflowId)) {
                throw new Exception('Missing workflow ID');
            }
            
            $result = PolarClinicalWorkflow::clockOut($workflowId, $location);
            
            if (is_array($result) && $result['success']) {
                echo json_encode(['success' => true]);
            } else {
                $error = is_array($result) ? $result['error'] : 'Failed to clock out';
                throw new Exception($error);
            }
            break;
            
        case 'complete_step':
            $stepId = $_POST['step_id'] ?? '';
            $stepData = $_POST['step_data'] ?? null;
            
            if (empty($stepId)) {
                throw new Exception('Missing step ID');
            }
            
            $result = PolarClinicalWorkflow::completeStep($stepId, $clinicianId, $stepData);
            
            if ($result !== false) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to complete step');
            }
            break;
            
        case 'scan_barcode':
            $workflowId = $_POST['workflow_id'] ?? '';
            $barcodeValue = $_POST['barcode_value'] ?? '';
            $barcodeType = $_POST['barcode_type'] ?? 'kit';
            $location = $_POST['location'] ?? null;
            $scannedData = $_POST['scanned_data'] ?? null;
            
            if (empty($workflowId) || empty($barcodeValue)) {
                throw new Exception('Missing required parameters');
            }
            
            $result = PolarClinicalWorkflow::scanBarcode($workflowId, $barcodeValue, $barcodeType, $clinicianId, $location, $scannedData);
            
            if ($result !== false) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Failed to scan barcode');
            }
            break;
            
        case 'get_workflow_status':
            $workflowId = $_POST['workflow_id'] ?? '';
            
            if (empty($workflowId)) {
                throw new Exception('Missing workflow ID');
            }
            
            $status = PolarClinicalWorkflow::getWorkflowStatus($workflowId);
            echo json_encode(['success' => true, 'status' => $status]);
            break;
            
        case 'get_workflow_steps':
            $workflowId = $_POST['workflow_id'] ?? '';
            
            if (empty($workflowId)) {
                throw new Exception('Missing workflow ID');
            }
            
            $steps = PolarClinicalWorkflow::getWorkflowSteps($workflowId);
            echo json_encode(['success' => true, 'steps' => $steps]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("POLAR Workflow Action Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
