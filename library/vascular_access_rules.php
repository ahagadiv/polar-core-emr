<?php

/**
 * Vascular Access Clinical Rules Implementation
 * Custom clinical rules for POLAR Healthcare vascular access procedures
 * 
 * @package   OpenEMR
 * @author    POLAR Healthcare
 * @copyright Copyright (c) 2025 POLAR Healthcare
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("clinical_rules.php");

/**
 * Vascular Access Clinical Rules Functions
 * These functions implement the specific logic for vascular access clinical reminders
 */

/**
 * Check if MD order is verified for vascular access procedure
 * 
 * @param int $patient_id Patient ID
 * @param array $checklist_data Saved checklist data
 * @return array Result with status and message
 */
function check_vascular_md_order($patient_id, $checklist_data = null) {
    // Check if there's a valid MD order in the patient's chart
    $query = "SELECT * FROM form_encounter fe 
              JOIN forms f ON fe.id = f.form_id 
              WHERE fe.pid = ? AND f.form_name = 'Vascular Access Order' 
              AND f.deleted = 0 
              ORDER BY fe.date DESC LIMIT 1";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['md_order_verified']) {
        return [
            'status' => 'pass',
            'message' => 'MD Order Verified: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'MD Order Required: Verify physician order for vascular access procedure',
            'priority' => 'high'
        ];
    }
    
    return [
        'status' => 'pass',
        'message' => 'MD Order Verified: Physician order confirmed for vascular access',
        'priority' => 'low'
    ];
}

/**
 * Check INR level for vascular access safety
 * 
 * @param int $patient_id Patient ID
 * @param array $checklist_data Saved checklist data
 * @return array Result with status and message
 */
function check_vascular_inr($patient_id, $checklist_data = null) {
    // Check for recent INR lab results
    $query = "SELECT pr.* FROM procedure_result pr 
              JOIN procedure_report ppr ON pr.procedure_report_id = ppr.procedure_report_id
              JOIN procedure_order po ON ppr.procedure_order_id = po.procedure_order_id
              WHERE po.patient_id = ? AND pr.result_code = 'INR' 
              AND pr.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              ORDER BY pr.date DESC LIMIT 1";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['inr_checked']) {
        return [
            'status' => 'pass',
            'message' => 'INR Check: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'INR Check Required: Recent INR level needed for vascular access safety',
            'priority' => 'high'
        ];
    }
    
    $inr_value = floatval($result['result']);
    
    if ($inr_value > 3.5) {
        return [
            'status' => 'alert',
            'message' => 'INR Elevated: INR level ' . $inr_value . ' - Consider postponing procedure',
            'priority' => 'high'
        ];
    } elseif ($inr_value < 1.5) {
        return [
            'status' => 'alert',
            'message' => 'INR Low: INR level ' . $inr_value . ' - Monitor for bleeding risk',
            'priority' => 'medium'
        ];
    }
    
    return [
        'status' => 'pass',
        'message' => 'INR Normal: INR level ' . $inr_value . ' - Safe for procedure',
        'priority' => 'low'
    ];
}

/**
 * Check for Chronic Kidney Disease (CKD) assessment
 * 
 * @param int $patient_id Patient ID
 * @return array Result with status and message
 */
function check_vascular_ckd($patient_id, $checklist_data = null) {
    // Check for CKD diagnosis in problem list
    $query = "SELECT * FROM lists WHERE pid = ? AND type = 'medical_problem' 
              AND (title LIKE '%kidney%' OR title LIKE '%renal%' OR title LIKE '%CKD%')
              AND enddate IS NULL";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['ckd_assessed']) {
        return [
            'status' => 'pass',
            'message' => 'CKD Assessment: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (!empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'CKD Present: ' . $result['title'] . ' - Use ultrasound guidance and consider vein mapping',
            'priority' => 'high'
        ];
    }
    
    // Check for recent creatinine/eGFR labs
    $query = "SELECT pr.* FROM procedure_result pr 
              JOIN procedure_report ppr ON pr.procedure_report_id = ppr.procedure_report_id
              JOIN procedure_order po ON ppr.procedure_order_id = po.procedure_order_id
              WHERE po.patient_id = ? AND (pr.result_code = 'CREAT' OR pr.result_code = 'EGFR') 
              AND pr.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              ORDER BY pr.date DESC LIMIT 1";
    
    $lab_result = sqlQuery($query, [$patient_id]);
    
    if (!empty($lab_result)) {
        $value = floatval($lab_result['result']);
        if ($lab_result['result_code'] == 'EGFR' && $value < 60) {
            return [
                'status' => 'alert',
                'message' => 'Reduced eGFR: ' . $value . ' - Consider CKD, use ultrasound guidance',
                'priority' => 'medium'
            ];
        }
    }
    
    return [
        'status' => 'alert',
        'message' => 'CKD Assessment Required: Verify no chronic kidney disease present',
        'priority' => 'high'
    ];
}

/**
 * Check for mastectomy history
 * 
 * @param int $patient_id Patient ID
 * @return array Result with status and message
 */
function check_vascular_mastectomy($patient_id, $checklist_data = null) {
    $query = "SELECT * FROM lists WHERE pid = ? AND type = 'medical_problem' 
              AND (title LIKE '%mastectomy%' OR title LIKE '%breast surgery%' OR title LIKE '%lymph node%')
              AND enddate IS NULL";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['mastectomy_checked']) {
        return [
            'status' => 'pass',
            'message' => 'Mastectomy Check: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (!empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'Mastectomy History: ' . $result['title'] . ' - Avoid affected side for PICC placement',
            'priority' => 'high'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Mastectomy Check Required: Verify no history of mastectomy or lymph node surgery',
        'priority' => 'high'
    ];
}

/**
 * Check for permanent pacemaker
 * 
 * @param int $patient_id Patient ID
 * @return array Result with status and message
 */
function check_vascular_pacemaker($patient_id, $checklist_data = null) {
    $query = "SELECT * FROM lists WHERE pid = ? AND type = 'medical_problem' 
              AND (title LIKE '%pacemaker%' OR title LIKE '%ICD%' OR title LIKE '%defibrillator%')
              AND enddate IS NULL";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['pacemaker_checked']) {
        return [
            'status' => 'pass',
            'message' => 'Pacemaker Check: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (!empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'Pacemaker Present: ' . $result['title'] . ' - Avoid subclavian approach, use ultrasound',
            'priority' => 'high'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Pacemaker Check Required: Verify no permanent pacemaker or ICD present',
        'priority' => 'high'
    ];
}

/**
 * Check for mediastinal mass
 * 
 * @param int $patient_id Patient ID
 * @return array Result with status and message
 */
function check_vascular_mediastinal($patient_id, $checklist_data = null) {
    $query = "SELECT * FROM lists WHERE pid = ? AND type = 'medical_problem' 
              AND (title LIKE '%mediastinal%' OR title LIKE '%mediastinum%' OR title LIKE '%thymoma%')
              AND enddate IS NULL";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['mediastinal_checked']) {
        return [
            'status' => 'pass',
            'message' => 'Mediastinal Check: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (!empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'Mediastinal Mass: ' . $result['title'] . ' - High risk for SVC syndrome, consider alternative access',
            'priority' => 'high'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Mediastinal Check Required: Verify no mediastinal mass present',
        'priority' => 'high'
    ];
}

/**
 * Check for double SVC history
 * 
 * @param int $patient_id Patient ID
 * @return array Result with status and message
 */
function check_vascular_double_svc($patient_id, $checklist_data = null) {
    $query = "SELECT * FROM lists WHERE pid = ? AND type = 'medical_problem' 
              AND (title LIKE '%double SVC%' OR title LIKE '%persistent left SVC%' OR title LIKE '%SVC anomaly%')
              AND enddate IS NULL";
    
    $result = sqlQuery($query, [$patient_id]);
    
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['double_svc_checked']) {
        return [
            'status' => 'pass',
            'message' => 'SVC Check: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    if (!empty($result)) {
        return [
            'status' => 'alert',
            'message' => 'Double SVC: ' . $result['title'] . ' - Anatomical variant present, use ultrasound guidance',
            'priority' => 'medium'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'SVC Check Required: Verify no double SVC or venous anomalies',
        'priority' => 'high'
    ];
}

/**
 * Check if ultrasound guidance is available
 * 
 * @param int $patient_id Patient ID
 * @param array $checklist_data Saved checklist data
 * @return array Result with status and message
 */
function check_vascular_ultrasound($patient_id, $checklist_data = null) {
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['ultrasound_available']) {
        return [
            'status' => 'pass',
            'message' => 'Ultrasound Guidance: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Ultrasound Guidance Required: Verify ultrasound equipment is available',
        'priority' => 'high'
    ];
}

/**
 * Check if vein mapping is completed
 * 
 * @param int $patient_id Patient ID
 * @param array $checklist_data Saved checklist data
 * @return array Result with status and message
 */
function check_vascular_vein_mapping($patient_id, $checklist_data = null) {
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['vein_mapping_done']) {
        return [
            'status' => 'pass',
            'message' => 'Vein Mapping: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Vein Mapping Required: Complete vein mapping if needed',
        'priority' => 'high'
    ];
}

/**
 * Check if informed consent is obtained
 * 
 * @param int $patient_id Patient ID
 * @param array $checklist_data Saved checklist data
 * @return array Result with status and message
 */
function check_vascular_consent($patient_id, $checklist_data = null) {
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['consent_obtained']) {
        return [
            'status' => 'pass',
            'message' => 'Informed Consent: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Informed Consent Required: Obtain patient consent for procedure',
        'priority' => 'high'
    ];
}

/**
 * Check if pre-procedure timeout is completed
 * 
 * @param int $patient_id Patient ID
 * @param array $checklist_data Saved checklist data
 * @return array Result with status and message
 */
function check_vascular_timeout($patient_id, $checklist_data = null) {
    // Check if it's been manually verified in checklist
    if ($checklist_data && $checklist_data['timeout_completed']) {
        return [
            'status' => 'pass',
            'message' => 'Pre-Procedure Timeout: ✅ Completed in checklist',
            'priority' => 'low'
        ];
    }
    
    return [
        'status' => 'alert',
        'message' => 'Pre-Procedure Timeout Required: Complete safety timeout before procedure',
        'priority' => 'high'
    ];
}

/**
 * Get all vascular access clinical reminders for a patient
 * 
 * @param int $patient_id Patient ID
 * @return array Array of all vascular access reminders
 */
function get_vascular_access_reminders($patient_id) {
    $reminders = [];
    
    // Get saved checklist data
    $checklist_data = sqlQuery("SELECT * FROM vascular_access_checklist WHERE patient_id = ?", [$patient_id]);
    
    // Check each vascular access rule
    $reminders[] = check_vascular_md_order($patient_id, $checklist_data);
    $reminders[] = check_vascular_inr($patient_id, $checklist_data);
    $reminders[] = check_vascular_ckd($patient_id, $checklist_data);
    $reminders[] = check_vascular_mastectomy($patient_id, $checklist_data);
    $reminders[] = check_vascular_pacemaker($patient_id, $checklist_data);
    $reminders[] = check_vascular_mediastinal($patient_id, $checklist_data);
    $reminders[] = check_vascular_double_svc($patient_id, $checklist_data);
    $reminders[] = check_vascular_ultrasound($patient_id, $checklist_data);
    $reminders[] = check_vascular_vein_mapping($patient_id, $checklist_data);
    $reminders[] = check_vascular_consent($patient_id, $checklist_data);
    $reminders[] = check_vascular_timeout($patient_id, $checklist_data);
    
    return $reminders;
}

/**
 * Display vascular access reminders in clinical reminders widget
 * 
 * @param int $patient_id Patient ID
 * @return string HTML output for reminders
 */
function display_vascular_access_reminders($patient_id) {
    $reminders = get_vascular_access_reminders($patient_id);
    $output = '';
    
    $output .= '<div class="vascular-access-reminders">';
    $output .= '<h4>🏥 POLAR Vascular Access Safety Checklist</h4>';
    
    foreach ($reminders as $reminder) {
        $class = '';
        $icon = '';
        
        switch ($reminder['status']) {
            case 'alert':
                $class = 'alert-danger';
                $icon = '⚠️';
                break;
            case 'pass':
                $class = 'alert-success';
                $icon = '✅';
                break;
            default:
                $class = 'alert-info';
                $icon = 'ℹ️';
        }
        
        $output .= '<div class="alert ' . $class . ' vascular-reminder">';
        $output .= '<strong>' . $icon . ' ' . $reminder['message'] . '</strong>';
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}

?>
