<?php

/**
 * POLAR Clinical Workflow Service
 * 
 * Handles the complete clinical workflow for POLAR Healthcare including:
 * - Clock in/out with EVV compliance
 * - Service-line specific flowsheets
 * - Barcode scanning integration
 * - Workflow validation and completion
 * 
 * @package OpenEMR
 * @author POLAR Healthcare Development Team
 */

class PolarClinicalWorkflow
{
    /**
     * Create a new clinical workflow for an appointment
     */
    public static function createWorkflow($appointmentId, $patientId, $clinicianId, $serviceLine)
    {
        $sql = "INSERT INTO polar_clinical_workflow 
                (appointment_id, patient_id, clinician_id, service_line, workflow_status) 
                VALUES (?, ?, ?, ?, 'assigned')";
        
        $result = sqlStatement($sql, [$appointmentId, $patientId, $clinicianId, $serviceLine]);
        
        if ($result !== false) {
            $workflowId = sqlInsertId();
            self::createWorkflowSteps($workflowId, $serviceLine);
            return $workflowId;
        }
        
        return false;
    }
    
    /**
     * Clock in a clinician to a workflow
     */
    public static function clockIn($workflowId, $location = null)
    {
        $sql = "UPDATE polar_clinical_workflow 
                SET workflow_status = 'clocked_in', 
                    clock_in_time = NOW(), 
                    clock_in_location = ?,
                    evv_verified = ?
                WHERE id = ?";
        
        $evvVerified = !empty($location) ? 1 : 0;
        
        return sqlStatement($sql, [$location, $evvVerified, $workflowId]);
    }
    
    /**
     * Clock out a clinician from a workflow
     */
    public static function clockOut($workflowId, $location = null)
    {
        // Check if all required steps are completed
        $sql = "SELECT COUNT(*) as incomplete_steps 
                FROM polar_workflow_steps 
                WHERE workflow_id = ? AND is_required = 1 AND is_completed = 0";
        
        $result = sqlQuery($sql, [$workflowId]);
        
        if ($result['incomplete_steps'] > 0) {
            return ['success' => false, 'error' => 'Cannot clock out. Required workflow steps not completed.'];
        }
        
        $sql = "UPDATE polar_clinical_workflow 
                SET workflow_status = 'clocked_out', 
                    clock_out_time = NOW(), 
                    clock_out_location = ?
                WHERE id = ?";
        
        $result = sqlStatement($sql, [$location, $workflowId]);
        
        return ['success' => $result !== false];
    }
    
    /**
     * Get workflow steps for a specific workflow
     */
    public static function getWorkflowSteps($workflowId)
    {
        $sql = "SELECT * FROM polar_workflow_steps 
                WHERE workflow_id = ? 
                ORDER BY sequence_order ASC";
        
        $result = sqlStatement($sql, [$workflowId]);
        $steps = [];
        
        while ($row = sqlFetchArray($result)) {
            $steps[] = $row;
        }
        
        return $steps;
    }
    
    /**
     * Complete a workflow step
     */
    public static function completeStep($stepId, $completedBy, $stepData = null)
    {
        $sql = "UPDATE polar_workflow_steps 
                SET is_completed = 1, 
                    completed_date = NOW(), 
                    completed_by = ?,
                    step_data = ?
                WHERE id = ?";
        
        return sqlStatement($sql, [$completedBy, $stepData, $stepId]);
    }
    
    /**
     * Scan a barcode and record it
     */
    public static function scanBarcode($workflowId, $barcodeValue, $barcodeType, $scannedBy, $location = null, $scannedData = null)
    {
        $sql = "INSERT INTO polar_barcode_tracking 
                (workflow_id, barcode_value, barcode_type, scanned_by, scan_location, scanned_data) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        return sqlStatement($sql, [$workflowId, $barcodeValue, $barcodeType, $scannedBy, $location, $scannedData]);
    }
    
    /**
     * Get flowsheet template for a service line
     */
    public static function getFlowsheetTemplate($serviceLine, $templateName = null)
    {
        $sql = "SELECT * FROM polar_flowsheet_templates 
                WHERE service_line = ? AND is_active = 1";
        
        $params = [$serviceLine];
        
        if ($templateName) {
            $sql .= " AND template_name = ?";
            $params[] = $templateName;
        }
        
        $sql .= " ORDER BY created_date DESC LIMIT 1";
        
        return sqlQuery($sql, $params);
    }
    
    /**
     * Get active workflows for a clinician
     */
    public static function getActiveWorkflows($clinicianId)
    {
        $sql = "SELECT pcw.*, pd.fname, pd.lname, pce.pc_title, pce.pc_eventDate, pce.pc_startTime
                FROM polar_clinical_workflow pcw
                JOIN patient_data pd ON pcw.patient_id = pd.pid
                JOIN openemr_postcalendar_events pce ON pcw.appointment_id = pce.pc_eid
                WHERE pcw.clinician_id = ? 
                AND pcw.workflow_status IN ('assigned', 'clocked_in', 'in_progress')
                ORDER BY pce.pc_eventDate, pce.pc_startTime";
        
        $result = sqlStatement($sql, [$clinicianId]);
        $workflows = [];
        
        while ($row = sqlFetchArray($result)) {
            $workflows[] = $row;
        }
        
        return $workflows;
    }
    
    /**
     * Create workflow steps from template
     */
    private static function createWorkflowSteps($workflowId, $serviceLine)
    {
        $template = self::getFlowsheetTemplate($serviceLine);
        
        if (!$template) {
            return false;
        }
        
        $templateData = json_decode($template['template_data'], true);
        $steps = $templateData['steps'] ?? [];
        
        foreach ($steps as $index => $step) {
            $sql = "INSERT INTO polar_workflow_steps 
                    (workflow_id, step_name, step_type, step_data, is_required, sequence_order) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            sqlStatement($sql, [
                $workflowId,
                $step['name'],
                $step['type'],
                json_encode($step),
                $step['required'] ?? true,
                $index + 1
            ]);
        }
        
        return true;
    }
    
    /**
     * Get workflow completion status
     */
    public static function getWorkflowStatus($workflowId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_steps,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_steps,
                    SUM(CASE WHEN is_required = 1 AND is_completed = 0 THEN 1 ELSE 0 END) as required_incomplete
                FROM polar_workflow_steps 
                WHERE workflow_id = ?";
        
        return sqlQuery($sql, [$workflowId]);
    }
    
    /**
     * Validate EVV requirements
     */
    public static function validateEVV($workflowId)
    {
        $sql = "SELECT evv_verified, clock_in_location, clock_in_time 
                FROM polar_clinical_workflow 
                WHERE id = ?";
        
        $result = sqlQuery($sql, [$workflowId]);
        
        if (!$result) {
            return ['valid' => false, 'error' => 'Workflow not found'];
        }
        
        if (empty($result['clock_in_location'])) {
            return ['valid' => false, 'error' => 'Location not captured for EVV compliance'];
        }
        
        if (!$result['evv_verified']) {
            return ['valid' => false, 'error' => 'EVV verification failed'];
        }
        
        return ['valid' => true];
    }
}

