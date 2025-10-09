<?php
/**
 * POLAR Healthcare PICC Intake Form
 * Custom form for processing FAX referrals and pre-screening patients
 */

require_once("../globals.php");
require_once("$srcdir/api.inc");
require_once("$srcdir/forms.inc");

use OpenEMR\Common\Csrf\CsrfUtils;

if (!CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
    CsrfUtils::csrfNotVerified();
}

if ($_POST["process"] == "true") {
    // Process the form data
    $newid = formSubmit("form_picc_intake", $_POST, $_GET["id"], $userauthorized);
    addForm($encounter, "PICC Intake Form", $newid, "picc_intake", $pid, $userauthorized);
    formHeader("Redirecting....");
    formJump();
    formFooter();
    exit;
}

// Get patient data
$patient_data = getPatientData($pid);
$form_data = formFetch("form_picc_intake", $id);

?>
<html>
<head>
    <title>POLAR Healthcare - PICC Intake Form</title>
    <link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">
    <style>
        .form-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 15px 0;
        }
        .form-section h3 {
            color: #667eea;
            margin-top: 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-row {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 10px 0;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .priority-stat {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .priority-urgent {
            background: #fd7e14;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
        .priority-routine {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body class="body_top">
    <form method="post" action="<?php echo $rootdir; ?>/interface/forms/picc_intake_form.php?id=<?php echo attr($id); ?>" onsubmit="return top.restoreSession()">
        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>" />
        <input type="hidden" name="process" value="true" />
        
        <div class="form-section">
            <h3>📋 Patient Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Patient Name:</label>
                    <input type="text" name="patient_name" value="<?php echo attr($patient_data['fname'] . ' ' . $patient_data['lname']); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>DOB:</label>
                    <input type="text" name="patient_dob" value="<?php echo attr($patient_data['DOB']); ?>" readonly />
                </div>
                <div class="form-group">
                    <label>MRN:</label>
                    <input type="text" name="patient_mrn" value="<?php echo attr($patient_data['pubpid']); ?>" readonly />
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>📄 Referral Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Referring Physician:</label>
                    <input type="text" name="referring_physician" value="<?php echo attr($form_data['referring_physician']); ?>" />
                </div>
                <div class="form-group">
                    <label>Referring Facility:</label>
                    <input type="text" name="referring_facility" value="<?php echo attr($form_data['referring_facility']); ?>" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>FAX Date:</label>
                    <input type="date" name="fax_date" value="<?php echo attr($form_data['fax_date']); ?>" />
                </div>
                <div class="form-group">
                    <label>FAX Number:</label>
                    <input type="text" name="fax_number" value="<?php echo attr($form_data['fax_number']); ?>" />
                </div>
            </div>
            <div class="form-group">
                <label>Clinical Indication:</label>
                <textarea name="clinical_indication" rows="3"><?php echo attr($form_data['clinical_indication']); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>🔍 Pre-screening Assessment</h3>
            <div class="form-group">
                <label>Patient Status:</label>
                <select name="patient_status">
                    <option value="picc_candidate" <?php echo ($form_data['patient_status'] == 'picc_candidate') ? 'selected' : ''; ?>>PICC Candidate</option>
                    <option value="midline_candidate" <?php echo ($form_data['patient_status'] == 'midline_candidate') ? 'selected' : ''; ?>>MIDLINE Candidate</option>
                    <option value="requires_clearance" <?php echo ($form_data['patient_status'] == 'requires_clearance') ? 'selected' : ''; ?>>Requires Clearance</option>
                    <option value="pending_payment" <?php echo ($form_data['patient_status'] == 'pending_payment') ? 'selected' : ''; ?>>Pending Payment</option>
                    <option value="ready_schedule" <?php echo ($form_data['patient_status'] == 'ready_schedule') ? 'selected' : ''; ?>>Ready to Schedule</option>
                </select>
            </div>
            
            <h4>Contraindications (Check all that apply):</h4>
            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" name="contraindications[]" value="active_infection" <?php echo (in_array('active_infection', $form_data['contraindications'] ?? [])) ? 'checked' : ''; ?> />
                    <label>Active Infection</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="contraindications[]" value="bleeding_disorder" <?php echo (in_array('bleeding_disorder', $form_data['contraindications'] ?? [])) ? 'checked' : ''; ?> />
                    <label>Bleeding Disorder</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="contraindications[]" value="thrombosis" <?php echo (in_array('thrombosis', $form_data['contraindications'] ?? [])) ? 'checked' : ''; ?> />
                    <label>History of Thrombosis</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="contraindications[]" value="allergy_contrast" <?php echo (in_array('allergy_contrast', $form_data['contraindications'] ?? [])) ? 'checked' : ''; ?> />
                    <label>Contrast Allergy</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="contraindications[]" value="pregnancy" <?php echo (in_array('pregnancy', $form_data['contraindications'] ?? [])) ? 'checked' : ''; ?> />
                    <label>Pregnancy</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="contraindications[]" value="renal_insufficiency" <?php echo (in_array('renal_insufficiency', $form_data['contraindications'] ?? [])) ? 'checked' : ''; ?> />
                    <label>Renal Insufficiency</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Additional Notes:</label>
                <textarea name="prescreening_notes" rows="3"><?php echo attr($form_data['prescreening_notes']); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>💰 Billing Information</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Insurance Type:</label>
                    <select name="insurance_type">
                        <option value="private_pay" <?php echo ($form_data['insurance_type'] == 'private_pay') ? 'selected' : ''; ?>>Private Pay</option>
                        <option value="medicare" <?php echo ($form_data['insurance_type'] == 'medicare') ? 'selected' : ''; ?>>Medicare</option>
                        <option value="medicaid" <?php echo ($form_data['insurance_type'] == 'medicaid') ? 'selected' : ''; ?>>Medicaid</option>
                        <option value="commercial" <?php echo ($form_data['insurance_type'] == 'commercial') ? 'selected' : ''; ?>>Commercial</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estimated Cost:</label>
                    <input type="number" name="estimated_cost" step="0.01" value="<?php echo attr($form_data['estimated_cost']); ?>" />
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Payment Status:</label>
                    <select name="payment_status">
                        <option value="pending" <?php echo ($form_data['payment_status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="partial" <?php echo ($form_data['payment_status'] == 'partial') ? 'selected' : ''; ?>>Partial Payment</option>
                        <option value="paid" <?php echo ($form_data['payment_status'] == 'paid') ? 'selected' : ''; ?>>Paid in Full</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Amount:</label>
                    <input type="number" name="payment_amount" step="0.01" value="<?php echo attr($form_data['payment_amount']); ?>" />
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>📅 Scheduling Priority</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Priority Level:</label>
                    <select name="priority_level" onchange="updatePriorityDisplay()">
                        <option value="routine" <?php echo ($form_data['priority_level'] == 'routine') ? 'selected' : ''; ?>>Routine</option>
                        <option value="urgent" <?php echo ($form_data['priority_level'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                        <option value="stat" <?php echo ($form_data['priority_level'] == 'stat') ? 'selected' : ''; ?>>STAT</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Preferred Date:</label>
                    <input type="date" name="preferred_date" value="<?php echo attr($form_data['preferred_date']); ?>" />
                </div>
            </div>
            <div class="form-group">
                <label>Special Instructions:</label>
                <textarea name="special_instructions" rows="2"><?php echo attr($form_data['special_instructions']); ?></textarea>
            </div>
        </div>

        <div style="text-align: center; margin: 20px 0;">
            <input type="submit" value="Save PICC Intake Form" class="btn btn-primary" style="padding: 10px 30px; font-size: 16px;" />
        </div>
    </form>

    <script>
        function updatePriorityDisplay() {
            const priority = document.querySelector('select[name="priority_level"]').value;
            const display = document.getElementById('priority-display');
            if (display) {
                display.className = 'priority-' + priority;
                display.textContent = priority.toUpperCase();
            }
        }
    </script>
</body>
</html>
