<?php

/**
 * POLAR Clinical Workflow - Calendar Integration
 * 
 * This file demonstrates how the clinical workflow integrates with OpenEMR's calendar system
 * to provide clinicians with their assigned patients and workflow management.
 */

require_once(__DIR__ . '/../../globals.php');
require_once(__DIR__ . '/../../library/PolarClinicalWorkflow.php');

// Get current clinician's assigned patients for today
$clinicianId = $_SESSION['authUser'];
$today = date('Y-m-d');

// Get appointments assigned to this clinician
$sql = "SELECT pce.pc_eid, pce.pc_pid, pce.pc_title, pce.pc_eventDate, pce.pc_startTime, 
               pce.pc_endTime, pd.fname, pd.lname, pd.pid,
               pcw.id as workflow_id, pcw.workflow_status, pcw.clock_in_time, pcw.clock_out_time
        FROM openemr_postcalendar_events pce
        JOIN patient_data pd ON pce.pc_pid = pd.pid
        LEFT JOIN polar_clinical_workflow pcw ON pce.pc_eid = pcw.appointment_id
        WHERE pce.pc_aid = ? 
        AND pce.pc_eventDate = ?
        AND pce.pc_apptstatus != 'cancelled'
        ORDER BY pce.pc_startTime";

$appointments = [];
$result = sqlStatement($sql, [$clinicianId, $today]);

while ($row = sqlFetchArray($result)) {
    $appointments[] = $row;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>POLAR Clinical Workflow - My Assignments</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot']; ?>/interface/themes/style_phela_v2.css">
    <style>
        .workflow-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 10px 0;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .workflow-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-assigned { background: #e3f2fd; color: #1976d2; }
        .status-clocked_in { background: #fff3e0; color: #f57c00; }
        .status-in_progress { background: #e8f5e8; color: #388e3c; }
        .status-completed { background: #f3e5f5; color: #7b1fa2; }
        .status-clocked_out { background: #fafafa; color: #616161; }
        
        .action-buttons {
            margin-top: 10px;
        }
        .btn {
            padding: 8px 16px;
            margin: 2px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #2196f3; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-danger { background: #f44336; color: white; }
        
        .patient-info {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .appointment-details {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🏥 POLAR Clinical Workflow - My Assignments</h2>
        <p><strong>Clinician:</strong> <?php echo htmlspecialchars($_SESSION['authUser']); ?> | <strong>Date:</strong> <?php echo date('M j, Y'); ?></p>
        
        <?php if (empty($appointments)): ?>
            <div class="workflow-card">
                <p>No appointments assigned for today.</p>
            </div>
        <?php else: ?>
            <?php foreach ($appointments as $appointment): ?>
                <div class="workflow-card">
                    <div class="patient-info">
                        <?php echo htmlspecialchars($appointment['fname'] . ' ' . $appointment['lname']); ?>
                        (ID: <?php echo htmlspecialchars($appointment['pid']); ?>)
                    </div>
                    
                    <div class="appointment-details">
                        <strong>Service:</strong> <?php echo htmlspecialchars($appointment['pc_title']); ?><br>
                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['pc_startTime'])); ?> - 
                                              <?php echo date('g:i A', strtotime($appointment['pc_endTime'])); ?><br>
                        
                        <?php if ($appointment['workflow_id']): ?>
                            <strong>Workflow Status:</strong> 
                            <span class="workflow-status status-<?php echo $appointment['workflow_status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $appointment['workflow_status'])); ?>
                            </span><br>
                            
                            <?php if ($appointment['clock_in_time']): ?>
                                <strong>Clocked In:</strong> <?php echo date('g:i A', strtotime($appointment['clock_in_time'])); ?><br>
                            <?php endif; ?>
                            
                            <?php if ($appointment['clock_out_time']): ?>
                                <strong>Clocked Out:</strong> <?php echo date('g:i A', strtotime($appointment['clock_out_time'])); ?><br>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if (!$appointment['workflow_id']): ?>
                            <!-- Create workflow for new appointment -->
                            <button class="btn btn-primary" onclick="createWorkflow(<?php echo $appointment['pc_eid']; ?>, <?php echo $appointment['pc_pid']; ?>)">
                                📋 Start Workflow
                            </button>
                        <?php else: ?>
                            <?php switch ($appointment['workflow_status']): 
                                case 'assigned': ?>
                                    <button class="btn btn-success" onclick="clockIn(<?php echo $appointment['workflow_id']; ?>)">
                                        🕐 Clock In
                                    </button>
                                    <?php break; ?>
                                    
                                <?php case 'clocked_in': ?>
                                    <button class="btn btn-primary" onclick="startWorkflow(<?php echo $appointment['workflow_id']; ?>)">
                                        📝 Start Documentation
                                    </button>
                                    <button class="btn btn-warning" onclick="clockOut(<?php echo $appointment['workflow_id']; ?>)">
                                        🏁 Clock Out
                                    </button>
                                    <?php break; ?>
                                    
                                <?php case 'in_progress': ?>
                                    <button class="btn btn-primary" onclick="continueWorkflow(<?php echo $appointment['workflow_id']; ?>)">
                                        📝 Continue Workflow
                                    </button>
                                    <button class="btn btn-warning" onclick="clockOut(<?php echo $appointment['workflow_id']; ?>)">
                                        🏁 Clock Out
                                    </button>
                                    <?php break; ?>
                                    
                                <?php case 'completed': ?>
                                    <button class="btn btn-warning" onclick="clockOut(<?php echo $appointment['workflow_id']; ?>)">
                                        🏁 Clock Out
                                    </button>
                                    <?php break; ?>
                                    
                                <?php case 'clocked_out': ?>
                                    <span style="color: #666;">✅ Workflow Completed</span>
                                    <?php break; ?>
                            <?php endswitch; ?>
                        <?php endif; ?>
                        
                        <button class="btn btn-primary" onclick="viewPatient(<?php echo $appointment['pc_pid']; ?>)">
                            👤 View Patient
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function createWorkflow(appointmentId, patientId) {
            if (confirm('Start clinical workflow for this patient?')) {
                // AJAX call to create workflow
                fetch('workflow_actions.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=create_workflow&appointment_id=${appointmentId}&patient_id=${patientId}&csrf_token=${getCsrfToken()}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }
        
        function clockIn(workflowId) {
            if (confirm('Clock in to this patient visit?')) {
                // Get location for EVV
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const location = `${position.coords.latitude},${position.coords.longitude}`;
                        performClockAction('clock_in', workflowId, location);
                    },
                    function() {
                        // Fallback if geolocation fails
                        performClockAction('clock_in', workflowId, null);
                    }
                );
            }
        }
        
        function clockOut(workflowId) {
            if (confirm('Clock out from this patient visit?')) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const location = `${position.coords.latitude},${position.coords.longitude}`;
                        performClockAction('clock_out', workflowId, location);
                    },
                    function() {
                        performClockAction('clock_out', workflowId, null);
                    }
                );
            }
        }
        
        function performClockAction(action, workflowId, location) {
            fetch('workflow_actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=${action}&workflow_id=${workflowId}&location=${location}&csrf_token=${getCsrfToken()}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        
        function startWorkflow(workflowId) {
            // Redirect to workflow interface
            window.location.href = `workflow_interface.php?workflow_id=${workflowId}`;
        }
        
        function continueWorkflow(workflowId) {
            window.location.href = `workflow_interface.php?workflow_id=${workflowId}`;
        }
        
        function viewPatient(patientId) {
            window.location.href = `<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?pid=${patientId}`;
        }
        
        function getCsrfToken() {
            // Get CSRF token from page or generate one
            return '<?php echo CsrfUtils::collectCsrfToken(); ?>';
        }
    </script>
</body>
</html>

