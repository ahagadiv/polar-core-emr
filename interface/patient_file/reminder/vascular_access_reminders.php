<?php

/**
 * Vascular Access Clinical Reminders Widget
 * Custom widget for POLAR Healthcare vascular access procedures
 * 
 * @package   OpenEMR
 * @author    POLAR Healthcare
 * @copyright Copyright (c) 2025 POLAR Healthcare
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../../globals.php");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/clinical_rules.php");
require_once("$srcdir/vascular_access_rules.php");

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is authenticated
if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
    header("Location: ../../login/login.php");
    exit();
}

use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Core\Header;

// Get patient ID from various sources
$patient_id = null;

// First try GET parameter
if (isset($_GET['patient_id']) && !empty($_GET['patient_id'])) {
    $patient_id = (int)$_GET['patient_id'];
}
// Then try session
elseif (isset($_SESSION['pid']) && !empty($_SESSION['pid'])) {
    $patient_id = (int)$_SESSION['pid'];
}
// Then try global $pid
elseif (isset($pid) && !empty($pid)) {
    $patient_id = (int)$pid;
}
// Finally try to get from URL if we're in a patient context
else {
    // Try to extract from referrer or current URL
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    
    if (preg_match('/[?&]pid=(\d+)/', $referrer, $matches)) {
        $patient_id = (int)$matches[1];
    } elseif (preg_match('/[?&]set_pid=(\d+)/', $referrer, $matches)) {
        $patient_id = (int)$matches[1];
    } elseif (preg_match('/[?&]pid=(\d+)/', $current_url, $matches)) {
        $patient_id = (int)$matches[1];
    } elseif (preg_match('/[?&]set_pid=(\d+)/', $current_url, $matches)) {
        $patient_id = (int)$matches[1];
    } else {
        $patient_id = 1; // Default fallback
    }
}

// Ensure we have a valid patient ID
if (!$patient_id || $patient_id < 1) {
    $patient_id = 1;
}

// Debug information (remove in production)
if (isset($_GET['debug'])) {
    echo "<!-- Debug Info: patient_id = $patient_id, GET = " . print_r($_GET, true) . ", SESSION = " . print_r($_SESSION, true) . " -->";
}

// Test database connection
$test_query = sqlQuery("SELECT COUNT(*) as count FROM vascular_access_checklist WHERE patient_id = ?", [$patient_id]);
echo "<!-- Test query result: " . print_r($test_query, true) . " -->";

?>

<html>
<head>
    <?php Header::setupHeader(['common']); ?>
    <title><?php echo xlt('Vascular Access Reminders'); ?></title>
    <script>
        // Simple form submission with session restoration
        function submitForm() {
            if (typeof top.restoreSession === 'function') {
                top.restoreSession();
            }
            return true;
        }
    </script>
    <style>
        .vascular-access-reminders {
            margin: 20px 0;
        }
        
        .vascular-access-reminders h4 {
            color: #2D6AE3;
            border-bottom: 2px solid #2D6AE3;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .vascular-reminder {
            margin-bottom: 10px;
            border-left: 4px solid;
            padding: 15px;
            border-radius: 5px;
        }
        
        .alert-danger {
            border-left-color: #dc3545;
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            border-left-color: #28a745;
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-info {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .reminder-priority {
            font-size: 0.9em;
            font-style: italic;
            margin-top: 5px;
        }
        
        .priority-high {
            color: #dc3545;
            font-weight: bold;
        }
        
        .priority-medium {
            color: #ffc107;
            font-weight: bold;
        }
        
        .priority-low {
            color: #28a745;
        }
    </style>
</head>

<body class='body_top'>
<div>
    <span class='title'><?php echo xlt('Vascular Access Clinical Reminders'); ?></span>
</div>

<div id='namecontainer_creminders' class='namecontainer_creminders' style='float: left; margin-right: 10px'>
    <?php echo xlt('for');?>&nbsp;
    <span class="title">
        <a href="../summary/demographics.php" onclick="top.restoreSession()"><?php echo text(getPatientName($patient_id)); ?></a>
    </span>
</div>

<div>
    <a href="../summary/demographics.php" class="btn btn-secondary" onclick="top.restoreSession()"><?php echo xlt('Back To Patient');?></a>
</div>

<br /><br /><br />

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <?php echo display_vascular_access_reminders($patient_id); ?>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>📋 Vascular Access Procedure Checklist</h5>
                </div>
                <div class="card-body">
                        <?php
                        // Load existing checklist data
                        $existing_checklist = sqlQuery("SELECT * FROM vascular_access_checklist WHERE patient_id = ?", [$patient_id]);
                        ?>
                        
                        <form id="checklistForm" method="post" action="vascular_access_reminders.php" onsubmit="return submitForm()">
                        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
                        <input type="hidden" name="patient_id" value="<?php echo attr($patient_id); ?>" />
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="md_order_verified" value="1" <?php echo ($existing_checklist && $existing_checklist['md_order_verified']) ? 'checked' : ''; ?>> 
                                MD Order Verified
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="inr_checked" value="1" <?php echo ($existing_checklist && $existing_checklist['inr_checked']) ? 'checked' : ''; ?>> 
                                INR Level Checked (within 7 days)
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="ckd_assessed" value="1" <?php echo ($existing_checklist && $existing_checklist['ckd_assessed']) ? 'checked' : ''; ?>> 
                                CKD Assessment Completed
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="mastectomy_checked" value="1" <?php echo ($existing_checklist && $existing_checklist['mastectomy_checked']) ? 'checked' : ''; ?>> 
                                Mastectomy History Checked
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="pacemaker_checked" value="1" <?php echo ($existing_checklist && $existing_checklist['pacemaker_checked']) ? 'checked' : ''; ?>> 
                                Permanent Pacemaker Checked
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="mediastinal_checked" value="1" <?php echo ($existing_checklist && $existing_checklist['mediastinal_checked']) ? 'checked' : ''; ?>> 
                                Mediastinal Mass Checked
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="double_svc_checked" value="1" <?php echo ($existing_checklist && $existing_checklist['double_svc_checked']) ? 'checked' : ''; ?>> 
                                Double SVC History Checked
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="ultrasound_available" value="1" <?php echo ($existing_checklist && $existing_checklist['ultrasound_available']) ? 'checked' : ''; ?>> 
                                Ultrasound Guidance Available
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="vein_mapping_done" value="1" <?php echo ($existing_checklist && $existing_checklist['vein_mapping_done']) ? 'checked' : ''; ?>> 
                                Vein Mapping Completed (if needed)
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="consent_obtained" value="1" <?php echo ($existing_checklist && $existing_checklist['consent_obtained']) ? 'checked' : ''; ?>> 
                                Informed Consent Obtained
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="timeout_completed" value="1" <?php echo ($existing_checklist && $existing_checklist['timeout_completed']) ? 'checked' : ''; ?>> 
                                Pre-Procedure Timeout Completed
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" name="save_checklist" value="1">Save Checklist</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Debug: Show all POST data
if (!empty($_POST)) {
    echo "<!-- POST Data: " . print_r($_POST, true) . " -->";
    error_log("Vascular Access POST Data: " . print_r($_POST, true));
}

// Handle form submission
if (isset($_POST['save_checklist']) && $_POST['save_checklist']) {
    error_log("Vascular Access Checklist Form Submitted for Patient ID: " . $patient_id);
    error_log("POST Data: " . print_r($_POST, true));
    
    if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
        error_log("CSRF Token verification failed");
        CsrfUtils::csrfNotVerified();
    }
    
    error_log("CSRF Token verification passed");
    
    // Save checklist data to database
    $checklist_data = [
        'patient_id' => $patient_id,
        'md_order_verified' => isset($_POST['md_order_verified']) ? 1 : 0,
        'inr_checked' => isset($_POST['inr_checked']) ? 1 : 0,
        'ckd_assessed' => isset($_POST['ckd_assessed']) ? 1 : 0,
        'mastectomy_checked' => isset($_POST['mastectomy_checked']) ? 1 : 0,
        'pacemaker_checked' => isset($_POST['pacemaker_checked']) ? 1 : 0,
        'mediastinal_checked' => isset($_POST['mediastinal_checked']) ? 1 : 0,
        'double_svc_checked' => isset($_POST['double_svc_checked']) ? 1 : 0,
        'ultrasound_available' => isset($_POST['ultrasound_available']) ? 1 : 0,
        'vein_mapping_done' => isset($_POST['vein_mapping_done']) ? 1 : 0,
        'consent_obtained' => isset($_POST['consent_obtained']) ? 1 : 0,
        'timeout_completed' => isset($_POST['timeout_completed']) ? 1 : 0,
        'timestamp' => date('Y-m-d H:i:s'),
        'user' => $_SESSION['authUser']
    ];
    
    // Check if record exists for this patient
    $existing = sqlQuery("SELECT id FROM vascular_access_checklist WHERE patient_id = ?", [$patient_id]);
    error_log("Existing record check: " . print_r($existing, true));
    error_log("Checklist data to save: " . print_r($checklist_data, true));
    
    if ($existing) {
        // Update existing record
        $sql = "UPDATE vascular_access_checklist SET 
                md_order_verified = ?, inr_checked = ?, ckd_assessed = ?, 
                mastectomy_checked = ?, pacemaker_checked = ?, mediastinal_checked = ?, 
                double_svc_checked = ?, ultrasound_available = ?, vein_mapping_done = ?, 
                consent_obtained = ?, timeout_completed = ?, timestamp = ?, user = ?
                WHERE patient_id = ?";
        
        $params = [
            $checklist_data['md_order_verified'], $checklist_data['inr_checked'], $checklist_data['ckd_assessed'],
            $checklist_data['mastectomy_checked'], $checklist_data['pacemaker_checked'], $checklist_data['mediastinal_checked'],
            $checklist_data['double_svc_checked'], $checklist_data['ultrasound_available'], $checklist_data['vein_mapping_done'],
            $checklist_data['consent_obtained'], $checklist_data['timeout_completed'], $checklist_data['timestamp'],
            $checklist_data['user'], $patient_id
        ];
        
        $result = sqlStatement($sql, $params);
        error_log("Update result: " . print_r($result, true));
        if ($result === false) {
            error_log("UPDATE failed: " . sqlError());
        }
    } else {
        // Insert new record
        $sql = "INSERT INTO vascular_access_checklist 
                (patient_id, md_order_verified, inr_checked, ckd_assessed, mastectomy_checked, 
                 pacemaker_checked, mediastinal_checked, double_svc_checked, ultrasound_available, 
                 vein_mapping_done, consent_obtained, timeout_completed, timestamp, user) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $checklist_data['patient_id'], $checklist_data['md_order_verified'], $checklist_data['inr_checked'],
            $checklist_data['ckd_assessed'], $checklist_data['mastectomy_checked'], $checklist_data['pacemaker_checked'],
            $checklist_data['mediastinal_checked'], $checklist_data['double_svc_checked'], $checklist_data['ultrasound_available'],
            $checklist_data['vein_mapping_done'], $checklist_data['consent_obtained'], $checklist_data['timeout_completed'],
            $checklist_data['timestamp'], $checklist_data['user']
        ];
        
        $result = sqlStatement($sql, $params);
        error_log("Insert result: " . print_r($result, true));
        if ($result === false) {
            error_log("INSERT failed: " . sqlError());
        }
    }
    
    // Show success message
    echo '<div class="alert alert-success">Vascular Access Checklist saved successfully!</div>';
    echo '<div class="alert alert-info">You can now return to the dashboard to see the updated status.</div>';
    echo '<p><a href="../summary/demographics.php?set_pid=' . $patient_id . '" class="btn btn-primary">Return to Dashboard</a></p>';
}
?>

</body>
</html>
