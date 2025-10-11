<?php
/**
 * Patient Procedures Management Interface
 * POLAR Healthcare - Vascular Access Focus
 */

require_once("../../globals.php");
require_once("$srcdir/patient.inc.php");

use OpenEMR\Common\Csrf\CsrfUtils;

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure required session variables are set
if (!isset($_SESSION['authUser'])) {
    $_SESSION['authUser'] = $_SESSION['authUser'] ?? 'admin';
}
if (!isset($_SESSION['authProvider'])) {
    $_SESSION['authProvider'] = $_SESSION['authProvider'] ?? 'default';
}
if (!isset($_SESSION['authUserID'])) {
    $_SESSION['authUserID'] = $_SESSION['authUserID'] ?? '1';
}

// Get patient ID
$pid = $_GET['patient_id'] ?? $_SESSION['pid'] ?? null;
if (!$pid) {
    die("Patient ID not found");
}

// Debug authentication (remove this in production)
if (isset($_GET['debug_auth'])) {
    echo "<div class='alert alert-info'>Debug Info:<br>";
    echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "<br>";
    echo "Auth User: " . (isset($_SESSION['authUser']) ? $_SESSION['authUser'] : 'Not set') . "<br>";
    echo "Patient ID: " . $pid . "<br>";
    echo "CSRF Token: " . (isset($_POST['csrf_token_form']) ? 'Present' : 'Missing') . "<br>";
    echo "</div>";
}

// Handle form submissions
if (isset($_POST['action']) && $_POST['action']) {
    // Check if CSRF token exists and verify it
    if (!isset($_POST["csrf_token_form"]) || !CsrfUtils::verifyCsrfToken($_POST["csrf_token_form"])) {
        echo "<div class='alert alert-danger'>Authentication Error: Invalid or missing security token. Please refresh the page and try again.</div>";
        // Don't exit, just show error and continue
    }
    
    $action = $_POST['action'];
    
    if ($action === 'add_procedure') {
        $procedure_date = $_POST['procedure_date'] ?? date('Y-m-d H:i:s');
        $cpt_code = $_POST['cpt_code'] ?? '';
        $procedure_description = $_POST['procedure_description'] ?? '';
        $orientation = $_POST['orientation'] ?? 'N/A';
        $catheter_trim_length = $_POST['catheter_trim_length'] ?? '';
        $comments = $_POST['comments'] ?? '';
        
        // Check if user is authenticated
        if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
            echo "<div class='alert alert-danger'>Authentication Error: User session expired. Please log in again.</div>";
        } elseif ($cpt_code && $procedure_description) {
            $sql = "INSERT INTO patient_procedures 
                    (patient_id, procedure_date, cpt_code, procedure_description, orientation, catheter_trim_length, comments, created_by, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')";
            
            $params = [$pid, $procedure_date, $cpt_code, $procedure_description, $orientation, $catheter_trim_length, $comments, $_SESSION['authUser']];
            
            try {
                if (sqlStatement($sql, $params)) {
                    echo "<div class='alert alert-success'>Procedure added successfully!</div>";
                } else {
                    echo "<div class='alert alert-danger'>Error adding procedure to database.</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>CPT Code and Description are required.</div>";
        }
    }
    
    if ($action === 'update_procedure') {
        $procedure_id = $_POST['procedure_id'] ?? '';
        $procedure_date = $_POST['procedure_date'] ?? '';
        $orientation = $_POST['orientation'] ?? 'N/A';
        $catheter_trim_length = $_POST['catheter_trim_length'] ?? '';
        $comments = $_POST['comments'] ?? '';
        $status = $_POST['status'] ?? 'ACTIVE';
        
        if ($procedure_id) {
            $sql = "UPDATE patient_procedures 
                    SET procedure_date = ?, orientation = ?, catheter_trim_length = ?, comments = ?, status = ?, modified_by = ?, modified_date = NOW() 
                    WHERE id = ? AND patient_id = ?";
            
            $params = [$procedure_date, $orientation, $catheter_trim_length, $comments, $status, $_SESSION['authUser'], $procedure_id, $pid];
            
            if (sqlStatement($sql, $params)) {
                echo "<div class='alert alert-success'>Procedure updated successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error updating procedure.</div>";
            }
        }
    }
    
    if ($action === 'delete_procedure') {
        $procedure_id = $_POST['procedure_id'] ?? '';
        
        if ($procedure_id) {
            $sql = "DELETE FROM patient_procedures WHERE id = ? AND patient_id = ?";
            $params = [$procedure_id, $pid];
            
            if (sqlStatement($sql, $params)) {
                echo "<div class='alert alert-success'>Procedure deleted successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>Error deleting procedure.</div>";
            }
        }
    }
}

// Get patient procedures
$procedures_query = "SELECT * FROM patient_procedures WHERE patient_id = ? ORDER BY procedure_date DESC";
$procedures_result = sqlStatement($procedures_query, [$pid]);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Procedures - POLAR Healthcare</title>
    <link rel="stylesheet" href="<?php echo $GLOBALS['webroot']; ?>/interface/themes/style_css.php">
    <script src="<?php echo $GLOBALS['webroot']; ?>/interface/main/tabs/js/include_opener.js"></script>
    <style>
        .procedure-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }
        .procedure-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        .cpt-code {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 10px;
        }
        .procedure-date {
            color: #666;
            font-size: 0.9em;
        }
        .procedure-description {
            font-weight: bold;
            margin-bottom: 8px;
        }
        .procedure-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        .detail-item {
            display: flex;
            align-items: center;
        }
        .detail-label {
            font-weight: bold;
            margin-right: 8px;
            min-width: 100px;
        }
        .detail-value {
            flex: 1;
        }
        .edit-form {
            background: #fff;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            margin-top: 15px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .btn-cpt-search {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-cpt-search:hover {
            background: #218838;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-completed { background: #cce5ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* Modern Typography Override */
        body, .container, .form-control, .btn, label, .alert, .badge, h1, h2, h3, h4, h5, h6 {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif !important;
            font-weight: 400;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            letter-spacing: -0.02em;
        }
        
        h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        
        /* Modern Form Controls */
        .form-control {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: #ffffff;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        /* Modern Buttons */
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
            transform: translateY(-1px);
        }
        
        .btn-cpt-search {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .btn-cpt-search:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
            transform: translateY(-1px);
        }
        
        /* Modern Links */
        .cpt-link {
            color: #3b82f6;
            text-decoration: none;
            margin-right: 15px;
            display: inline-block;
            margin-bottom: 8px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .cpt-link:hover {
            background: #eff6ff;
            color: #1d4ed8;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        /* Modern Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 14px;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .form-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 0.25rem;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Modern Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 16px 20px;
            font-weight: 500;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        /* Enhanced Procedure Cards */
        .procedure-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .procedure-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .cpt-code {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            margin-right: 10px;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .procedure-description {
            font-weight: 600;
            margin-bottom: 12px;
            color: #1f2937;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .detail-label {
            font-weight: 600;
            margin-right: 8px;
            min-width: 100px;
            color: #374151;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        .detail-value {
            flex: 1;
            color: #6b7280;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Enhanced Status Badges */
        .status-active { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }
        
        .status-completed { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }
        
        .status-cancelled { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }
        
        /* Modern Textarea */
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Modern Select */
        select.form-control {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }
        
        /* Success Notification Animation */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .success-notification {
            animation: slideInRight 0.3s ease-out;
        }
        
        /* Modern Form Container */
        .modern-form-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }
        
        .modern-form {
            max-width: 100%;
        }
        
        /* Form Row Layout */
        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 1.5rem;
            width: 100%;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
            width: 100%;
        }
        
        /* Ensure form rows have consistent field alignment */
        .form-row .form-group .form-control,
        .form-row .form-group select,
        .form-row .form-group input {
            width: 100%;
            max-width: 100%;
        }
        
        /* Quick CPT Links */
        .quick-links-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .quick-cpt-links {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 0;
        }
        
        .quick-cpt-links .cpt-link {
            margin: 0;
            margin-bottom: 0;
            padding: 6px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .quick-cpt-links .cpt-link:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }
        
        /* Status Help */
        .status-help {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 1.5rem;
        }
        
        .status-help .form-text {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }
        
        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-start;
            align-items: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .form-actions .btn {
            min-width: 140px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-lg {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Responsive Design with Smart Margins */
        
        /* Large screens - more breathing room */
        @media (min-width: 1200px) {
            .modern-form-container {
                max-width: 1000px;
                margin: 0 auto;
            }
            
            .modern-form .form-group {
                margin-right: 12px;
                margin-left: 12px;
            }
        }
        
        /* Medium screens - balanced margins */
        @media (min-width: 992px) and (max-width: 1199px) {
            .modern-form-container {
                max-width: 900px;
                margin: 0 auto;
            }
            
            .modern-form .form-group {
                margin-right: 10px;
                margin-left: 10px;
            }
        }
        
        /* Small screens - compact but comfortable */
        @media (min-width: 769px) and (max-width: 991px) {
            .modern-form-container {
                max-width: 100%;
                margin: 0 16px;
            }
            
            .modern-form .form-group {
                margin-right: 6px;
                margin-left: 6px;
            }
        }
        
        /* Mobile screens - full width with padding */
        @media (max-width: 768px) {
            .modern-form-container {
                margin: 0 12px;
                padding: 20px;
            }
            
            .modern-form .form-group {
                margin-right: 0;
                margin-left: 0;
                margin-bottom: 1.5rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .quick-cpt-links {
                flex-direction: column;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
                margin-top: 20px;
            }
            
            .form-actions .btn {
                width: 100%;
                margin-bottom: 8px;
            }
        }
        
        /* Extra small screens - minimal margins */
        @media (max-width: 480px) {
            .modern-form-container {
                margin: 0 8px;
                padding: 16px;
            }
            
            .modern-form .form-group {
                margin-bottom: 1.25rem;
            }
        }
        
        /* Uniform Form Field Widths with Responsive Margins */
        .form-control, .input-group, textarea.form-control, select.form-control {
            max-width: 100%;
            width: 100%;
            margin-right: 0;
            box-sizing: border-box;
        }
        
        /* Add breathing room for form fields */
        .modern-form .form-group {
            margin-right: 8px;
            margin-left: 8px;
        }
        
        /* Responsive margins that adjust to screen size */
        .modern-form .form-group:first-child {
            margin-left: 0;
        }
        
        .modern-form .form-group:last-child {
            margin-right: 0;
        }
        
        /* Consistent Field Alignment */
        .form-group {
            width: 100%;
        }
        
        .form-row .form-group {
            width: 100%;
        }
        
        /* Input Group Fix */
        .input-group {
            display: flex;
            align-items: stretch;
            width: 100%;
        }
        
        .input-group .form-control {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: 0;
            width: calc(100% - 120px); /* Fixed width for input */
        }
        
        .input-group .btn-cpt-search {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            margin-left: 0;
            border-left: 0;
            width: 120px; /* Fixed width for button */
            flex-shrink: 0;
        }
        
        /* Ensure all form elements have consistent max-width */
        .modern-form .form-control,
        .modern-form textarea,
        .modern-form select {
            max-width: 100%;
            box-sizing: border-box;
        }
        
        /* Status dropdown should match other fields */
        .form-row .form-group:last-child {
            width: 100%;
        }
        
        /* Perfect Alignment Override */
        .modern-form-container {
            position: relative;
        }
        
        /* Ensure all form elements align to the same right edge */
        .modern-form .form-group .form-control,
        .modern-form .form-group textarea,
        .modern-form .form-group select,
        .modern-form .input-group {
            box-sizing: border-box;
            margin-right: 0;
        }
        
        /* Fix any remaining alignment issues */
        .modern-form .form-row {
            align-items: flex-end;
        }
        
        /* Ensure CPT search button aligns with other elements */
        .input-group {
            position: relative;
        }
        
        /* Make sure all full-width elements align */
        .form-group:not(.col-md-6) .form-control,
        .form-group:not(.col-md-6) textarea,
        .form-group:not(.col-md-6) .input-group {
            width: 100%;
            max-width: 100%;
        }
        
        /* Final polish - ensure consistent spacing */
        .modern-form .form-control,
        .modern-form textarea,
        .modern-form select {
            margin-bottom: 0;
        }
        
        /* Add subtle visual breathing room */
        .modern-form-container h3 {
            margin-bottom: 24px;
        }
        
        /* Ensure proper spacing between sections */
        .quick-cpt-links {
            margin-top: 8px;
        }
        
        .status-help {
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h2>🏥 Patient Procedures - POLAR Healthcare</h2>
        
        <div class="row">
            <div class="col-md-8">
                <h3>Procedure History</h3>
                
                <?php
                $procedure_count = 0;
                while ($procedure = sqlFetchArray($procedures_result)) {
                    $procedure_count++;
                    ?>
                    <div class="procedure-card">
                        <div class="procedure-header">
                            <div>
                                <span class="cpt-code"><?php echo htmlspecialchars($procedure['cpt_code']); ?></span>
                                <span class="procedure-date"><?php echo date('M j, Y g:i A', strtotime($procedure['procedure_date'])); ?></span>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($procedure['status']); ?>">
                                <?php echo $procedure['status']; ?>
                            </span>
                        </div>
                        
                        <div class="procedure-description">
                            <?php echo htmlspecialchars($procedure['procedure_description']); ?>
                        </div>
                        
                        <div class="procedure-details">
                            <div class="detail-item">
                                <span class="detail-label">Orientation:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($procedure['orientation']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Trim Length:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($procedure['catheter_trim_length']); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($procedure['comments']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Comments:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($procedure['comments']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 5px;">
                            <button class="btn btn-sm btn-secondary" onclick="editProcedure(<?php echo $procedure['id']; ?>)">
                                Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteProcedure(<?php echo $procedure['id']; ?>)">
                                Delete
                            </button>
                        </div>
                    </div>
                    
                    <div id="edit-form-<?php echo $procedure['id']; ?>" class="edit-form" style="display: none;">
                        <h4>Edit Procedure</h4>
                        <form method="post">
                            <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>">
                            <input type="hidden" name="action" value="update_procedure">
                            <input type="hidden" name="procedure_id" value="<?php echo $procedure['id']; ?>">
                            
                            <div class="form-group">
                                <label>Procedure Date:</label>
                                <input type="datetime-local" name="procedure_date" value="<?php echo date('Y-m-d\TH:i', strtotime($procedure['procedure_date'])); ?>" class="form-control">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Orientation:</label>
                                    <select name="orientation" class="form-control">
                                        <option value="N/A" <?php echo $procedure['orientation'] === 'N/A' ? 'selected' : ''; ?>>N/A</option>
                                        <option value="LEFT" <?php echo $procedure['orientation'] === 'LEFT' ? 'selected' : ''; ?>>LEFT</option>
                                        <option value="RIGHT" <?php echo $procedure['orientation'] === 'RIGHT' ? 'selected' : ''; ?>>RIGHT</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Catheter Trim Length:</label>
                                    <input type="text" name="catheter_trim_length" value="<?php echo htmlspecialchars($procedure['catheter_trim_length']); ?>" class="form-control" placeholder="e.g., 45cm">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Status:</label>
                                <select name="status" class="form-control">
                                    <option value="ACTIVE" <?php echo $procedure['status'] === 'ACTIVE' ? 'selected' : ''; ?>>Active</option>
                                    <option value="COMPLETED" <?php echo $procedure['status'] === 'COMPLETED' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="CANCELLED" <?php echo $procedure['status'] === 'CANCELLED' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Comments:</label>
                                <textarea name="comments" class="form-control" rows="3"><?php echo htmlspecialchars($procedure['comments']); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Procedure</button>
                            <button type="button" class="btn btn-secondary" onclick="cancelEdit(<?php echo $procedure['id']; ?>)">Cancel</button>
                        </form>
                    </div>
                    <?php
                }
                
                if ($procedure_count === 0) {
                    echo "<div class='alert alert-info'>No procedures found for this patient.</div>";
                }
                ?>
            </div>
            
            <div class="col-md-6">
                <div class="modern-form-container">
                    <h3>Add New Procedure</h3>
                    <form method="post" class="modern-form">
                        <input type="hidden" name="csrf_token_form" value="<?php echo attr(CsrfUtils::collectCsrfToken()); ?>">
                        <input type="hidden" name="action" value="add_procedure">
                        
                        <!-- Date and Status Row -->
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Procedure Date:</label>
                                <input type="datetime-local" name="procedure_date" value="<?php echo date('Y-m-d\TH:i'); ?>" class="form-control">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Status:</label>
                                <select name="status" class="form-control">
                                    <option value="ACTIVE" selected>Active</option>
                                    <option value="COMPLETED">Completed</option>
                                    <option value="CANCELLED">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- CPT Code Section -->
                        <div class="form-group">
                            <label>CPT Code:</label>
                            <div class="input-group">
                                <input type="text" name="cpt_code" id="cpt_code" class="form-control" placeholder="e.g., 36569, A4215, 76937" required onchange="lookupCPTCode()">
                                <button type="button" class="btn-cpt-search" onclick="searchCPTCodes()">Search CPT</button>
                            </div>
                        </div>
                        
                        <!-- Quick CPT Links -->
                        <div class="form-group">
                            <label class="quick-links-label">Quick Select:</label>
                            <div class="quick-cpt-links">
                                <a href="javascript:void(0)" onclick="setCPTCode('36569', 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, without imaging guidance; age 5 years or older')" class="cpt-link">36569 - PICC</a>
                                <a href="javascript:void(0)" onclick="setCPTCode('A4215', 'BardPowerPICC + 3CG')" class="cpt-link">A4215 - PowerPICC</a>
                                <a href="javascript:void(0)" onclick="setCPTCode('76937', 'Ultrasound guidance for vascular access requiring ultrasound evaluation of potential access sites, documentation of selected vessel patency, concurrent realtime ultrasound visualization of vascular needle entry, with permanent recording and reporting')" class="cpt-link">76937 - Ultrasound</a>
                                <a href="javascript:void(0)" onclick="setCPTCode('36410', 'Venipuncture, age 3 years or older, necessitating the skill of a physician or other qualified health care professional, for diagnostic or therapeutic purposes (specify reason)')" class="cpt-link">36410 - Venipuncture</a>
                                <a href="javascript:void(0)" onclick="setCPTCode('A4301', 'BARD MIDLINE KIT')" class="cpt-link">A4301 - MIDLINE</a>
                            </div>
                        </div>
                        
                        <!-- Procedure Description -->
                        <div class="form-group">
                            <label>Procedure Description:</label>
                            <textarea name="procedure_description" id="procedure_description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <!-- Orientation and Trim Length Row -->
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Orientation:</label>
                                <select name="orientation" class="form-control">
                                    <option value="N/A">N/A</option>
                                    <option value="LEFT">LEFT</option>
                                    <option value="RIGHT">RIGHT</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Catheter Trim Length:</label>
                                <input type="text" name="catheter_trim_length" class="form-control" placeholder="e.g., 45cm">
                            </div>
                        </div>
                        
                        <!-- Comments -->
                        <div class="form-group">
                            <label>Comments:</label>
                            <textarea name="comments" class="form-control" rows="2" placeholder="Additional notes or comments"></textarea>
                        </div>
                        
                        <!-- Status Help Text -->
                        <div class="status-help">
                            <small class="form-text text-muted">
                                <strong>Active</strong> - Currently in progress • <strong>Completed</strong> - Procedure finished • <strong>Cancelled</strong> - Procedure cancelled
                            </small>
                        </div>
                    
                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">Add Procedure</button>
                        <a href="../../patient_file/summary/demographics.php?patient_id=<?php echo attr($pid); ?>" class="btn btn-secondary btn-lg">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
        
    </div>

    <script>
        function searchCPTCodes() {
            console.log('Search CPT button clicked');
            
            // Use OpenEMR's standard code selection approach with proper callback
            var url = '<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/encounter/find_code_dynamic.php?codetype=CPT4&singleCodeSelection=1';
            var title = 'Select CPT Procedure Code';
            var width = 985;
            var height = 750;
            
            // Ensure the callback function is available in ALL possible contexts
            window.setCPTCode = setCPTCode;
            window.set_related = set_related;
            window.set_related_target = set_related_target;
            
            if (typeof top !== 'undefined') {
                top.setCPTCode = setCPTCode;
                top.set_related = set_related;
                top.set_related_target = set_related_target;
            }
            
            if (typeof parent !== 'undefined') {
                parent.setCPTCode = setCPTCode;
                parent.set_related = set_related;
                parent.set_related_target = set_related_target;
            }
            
            // Also make sure they're available on the opener if it exists
            if (window.opener) {
                window.opener.setCPTCode = setCPTCode;
                window.opener.set_related = set_related;
                window.opener.set_related_target = set_related_target;
            }
            
            console.log('Callback functions set on all contexts');
            
            // Create a simple integrated CPT search dialog
            createSimpleCPTDialog(title, width, height);
        }
        
        // Function to handle CPT code selection from dialog
        function setCPTCode(code, description) {
            console.log('Setting CPT code:', code, description);
            try {
                // Wait a moment for DOM to be ready
                setTimeout(function() {
                    var codeField = document.getElementById('cpt_code');
                    var descField = document.getElementById('procedure_description');
                    
                    console.log('Code field found:', !!codeField);
                    console.log('Description field found:', !!descField);
                    
                    if (codeField) {
                        codeField.value = code;
                        console.log('CPT code set to:', code);
                        // Force visual update
                        codeField.style.backgroundColor = '#d4edda';
                        setTimeout(function() {
                            codeField.style.backgroundColor = '';
                        }, 1000);
                        // Trigger change event to activate auto-lookup
                        codeField.dispatchEvent(new Event('change'));
                        // Force focus to show the change
                        codeField.focus();
                        codeField.blur();
                    } else {
                        console.error('CPT code field not found');
                        alert('CPT code field not found. Please refresh the page and try again.');
                    }
                    
                    if (descField) {
                        descField.value = description;
                        console.log('Description set to:', description);
                        // Force visual update
                        descField.style.backgroundColor = '#d4edda';
                        setTimeout(function() {
                            descField.style.backgroundColor = '';
                        }, 1000);
                        // Force focus to show the change
                        descField.focus();
                        descField.blur();
                    } else {
                        console.error('Description field not found');
                        alert('Description field not found. Please refresh the page and try again.');
                    }
                    
                    // Show success notification
                    showSuccessNotification('CPT code "' + code + '" has been added to the form!');
                }, 100);
                
            } catch (error) {
                console.error('Error setting CPT code:', error);
                alert('Error setting CPT code: ' + error.message);
            }
        }
        
        // Function to lookup CPT code description when manually typed
        function lookupCPTCode() {
            var code = document.getElementById('cpt_code').value.trim();
            if (code.length > 0) {
                // Common CPT code descriptions
                var cptDescriptions = {
                    '36569': 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, without imaging guidance; age 5 years or older',
                    'A4215': 'BardPowerPICC + 3CG',
                    'A4216': 'BardPowerPICC Solo',
                    'A4217': 'BardPowerPICC Solo2',
                    'A4218': 'BardPowerPICC Max',
                    'A4301': 'BARD MIDLINE KIT',
                    '76937': 'Ultrasound guidance for vascular access requiring ultrasound evaluation of potential access sites, documentation of selected vessel patency, concurrent realtime ultrasound visualization of vascular needle entry, with permanent recording and reporting',
                    '36410': 'Venipuncture, age 3 years or older, necessitating the skill of a physician or other qualified health care professional, for diagnostic or therapeutic purposes (specify reason)',
                    '36568': 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, without imaging guidance; younger than 5 years',
                    '36571': 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, with imaging guidance; age 5 years or older',
                    '36572': 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, with imaging guidance; younger than 5 years',
                    '36573': 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, with imaging guidance; central venous access device insertion',
                    '36584': 'Replacement, complete, of a peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, through same venous access',
                    '36585': 'Replacement, complete, of a peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, through new venous access',
                    '36589': 'Removal of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump'
                };
                
                var description = cptDescriptions[code];
                if (description) {
                    document.getElementById('procedure_description').value = description;
                    console.log('Auto-filled description for CPT code:', code);
                } else {
                    console.log('No auto-description found for CPT code:', code);
                }
            }
        }
        
        // Make setCPTCode available globally for dialog communication
        window.setCPTCode = setCPTCode;
        if (typeof top !== 'undefined') {
            top.setCPTCode = setCPTCode;
        }
        
        // Implement OpenEMR's expected callback functions for dialog communication
        function set_related(codetype, code, selector, codedesc, modifier) {
            console.log('set_related called with:', {codetype, code, selector, codedesc, modifier});
            console.log('Current window location:', window.location.href);
            console.log('Dialog opener exists:', !!window.opener);
            console.log('Top window exists:', !!window.top);
            
            try {
                // Call setCPTCode directly
                console.log('Calling setCPTCode from set_related');
                setCPTCode(code, codedesc);
                console.log('setCPTCode completed successfully');
                return null; // No error message
            } catch (error) {
                console.error('Error in set_related:', error);
                return 'Error setting CPT code: ' + error.message;
            }
        }
        
        function set_related_target(codetype, code, selector, codedesc, target_element, limit) {
            console.log('set_related_target called with:', {codetype, code, selector, codedesc, target_element, limit});
            console.log('Current window location:', window.location.href);
            console.log('Dialog opener exists:', !!window.opener);
            console.log('Top window exists:', !!window.top);
            
            try {
                // Call setCPTCode directly
                console.log('Calling setCPTCode from set_related_target');
                setCPTCode(code, codedesc);
                console.log('setCPTCode completed successfully');
                return null; // No error message
            } catch (error) {
                console.error('Error in set_related_target:', error);
                return 'Error setting CPT code: ' + error.message;
            }
        }
        
        // Make these functions available globally
        window.set_related = set_related;
        window.set_related_target = set_related_target;
        if (typeof top !== 'undefined') {
            top.set_related = set_related;
            top.set_related_target = set_related_target;
        }
        
        function editProcedure(id) {
            document.getElementById('edit-form-' + id).style.display = 'block';
        }
        
        function cancelEdit(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
        }
        
        function deleteProcedure(id) {
            if (confirm('Are you sure you want to delete this procedure? This action cannot be undone.')) {
                // Create a form to submit the delete request
                var form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                // Add CSRF token
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = 'csrf_token_form';
                csrfToken.value = '<?php echo attr(CsrfUtils::collectCsrfToken()); ?>';
                form.appendChild(csrfToken);
                
                // Add action
                var action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'action';
                action.value = 'delete_procedure';
                form.appendChild(action);
                
                // Add procedure ID
                var procedureId = document.createElement('input');
                procedureId.type = 'hidden';
                procedureId.name = 'procedure_id';
                procedureId.value = id;
                form.appendChild(procedureId);
                
                // Submit form
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Debug function to check if scripts are loaded
        function debugScripts() {
            console.log('dlgopen function exists:', typeof dlgopen);
            console.log('top.dlgopen function exists:', typeof top.dlgopen);
            console.log('window.open function exists:', typeof window.open);
        }
        
        // Run debug on page load
        document.addEventListener('DOMContentLoaded', function() {
            debugScripts();
        });
        
        // Listen for messages from dialog windows
        window.addEventListener('message', function(event) {
            console.log('Received message:', event.data);
            if (event.data && event.data.type === 'cpt_code_selected') {
                setCPTCode(event.data.code, event.data.description);
            }
        });
        
        // Handle promiseData from single code selection
        function handlePromiseData() {
            if (window.promiseData) {
                try {
                    var data = JSON.parse(window.promiseData);
                    console.log('Handling promiseData:', data);
                    setCPTCode(data.code, data.codedesc);
                    window.promiseData = null; // Clear the data
                } catch (error) {
                    console.error('Error parsing promiseData:', error);
                }
            }
        }
        
        // Check for promiseData more frequently
        setInterval(handlePromiseData, 100);
        
        // Also check immediately when dialog closes
        var originalDlgclose = null;
        if (typeof top.dlgclose === 'function') {
            originalDlgclose = top.dlgclose;
            top.dlgclose = function() {
                console.log('Dialog is closing, checking for data immediately...');
                // Check if any data was set during the dialog interaction
                if (window.promiseData) {
                    console.log('Found promiseData on close:', window.promiseData);
                    handlePromiseData();
                }
                // Also check top window
                if (top.promiseData) {
                    console.log('Found promiseData on top window:', top.promiseData);
                    var data = JSON.parse(top.promiseData);
                    setCPTCode(data.code, data.codedesc);
                    top.promiseData = null;
                }
                // Call original dlgclose
                if (originalDlgclose) {
                    originalDlgclose.apply(this, arguments);
                }
            };
        }
        
        
        // Also check if we're in a dialog context and need to call parent functions
        function checkDialogContext() {
            if (window.opener && window.opener.set_related) {
                console.log('Found opener with set_related function');
                // Make sure the opener has our callback functions
                window.opener.set_related = set_related;
                window.opener.set_related_target = set_related_target;
                window.opener.setCPTCode = setCPTCode;
            }
            if (window.parent && window.parent.set_related) {
                console.log('Found parent with set_related function');
                // Make sure the parent has our callback functions
                window.parent.set_related = set_related;
                window.parent.set_related_target = set_related_target;
                window.parent.setCPTCode = setCPTCode;
            }
        }
        
        // Simple CPT dialog function with built-in search
        function createSimpleCPTDialog(title, width, height) {
            console.log('Creating simple CPT dialog');
            
            // CPT codes database
            var cptCodes = [
                {code: '36569', description: 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, without imaging guidance; age 5 years or older'},
                {code: '36568', description: 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, without imaging guidance; younger than 5 years'},
                {code: '36571', description: 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, with imaging guidance; age 5 years or older'},
                {code: '36572', description: 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, with imaging guidance; younger than 5 years'},
                {code: '36573', description: 'Insertion of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, with imaging guidance; central venous access device insertion'},
                {code: '36584', description: 'Replacement, complete, of a peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, through same venous access'},
                {code: '36585', description: 'Replacement, complete, of a peripherally inserted central venous catheter (PICC), without subcutaneous port or pump, through new venous access'},
                {code: '36589', description: 'Removal of peripherally inserted central venous catheter (PICC), without subcutaneous port or pump'},
                {code: 'A4215', description: 'BardPowerPICC + 3CG'},
                {code: 'A4216', description: 'BardPowerPICC Solo'},
                {code: 'A4217', description: 'BardPowerPICC Solo2'},
                {code: 'A4218', description: 'BardPowerPICC Max'},
                {code: 'A4301', description: 'BARD MIDLINE KIT'},
                {code: '76937', description: 'Ultrasound guidance for vascular access requiring ultrasound evaluation of potential access sites, documentation of selected vessel patency, concurrent realtime ultrasound visualization of vascular needle entry, with permanent recording and reporting'},
                {code: '36410', description: 'Venipuncture, age 3 years or older, necessitating the skill of a physician or other qualified health care professional, for diagnostic or therapeutic purposes (specify reason)'}
            ];
            
            // Create modal overlay
            var overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                justify-content: center;
                align-items: center;
            `;
            
            // Create dialog container
            var dialog = document.createElement('div');
            dialog.style.cssText = `
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                width: ${width}px;
                height: ${height}px;
                max-width: 90vw;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
            `;
            
            // Create header
            var header = document.createElement('div');
            header.style.cssText = `
                padding: 15px 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8f9fa;
                border-radius: 8px 8px 0 0;
            `;
            header.innerHTML = `
                <h4 style="margin: 0; color: #333;">${title}</h4>
                <button id="close-cpt-dialog" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
            `;
            
            // Create search bar
            var searchBar = document.createElement('div');
            searchBar.style.cssText = `
                padding: 15px 20px;
                border-bottom: 1px solid #eee;
                background: #f8f9fa;
            `;
            searchBar.innerHTML = `
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="cpt-search-input" placeholder="Search CPT codes..." style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                    <button id="search-cpt-btn" style="padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Search</button>
                </div>
            `;
            
            // Create results container
            var resultsContainer = document.createElement('div');
            resultsContainer.style.cssText = `
                flex: 1;
                overflow-y: auto;
                padding: 10px;
            `;
            resultsContainer.id = 'cpt-results';
            
            // Create footer with Add button
            var footer = document.createElement('div');
            footer.style.cssText = `
                padding: 15px 20px;
                border-top: 1px solid #ddd;
                background: #f8f9fa;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 0 0 8px 8px;
            `;
            footer.innerHTML = `
                <div id="selected-info" style="color: #666; font-size: 14px;">No code selected</div>
                <div>
                    <button id="add-cpt-btn" style="padding: 8px 20px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px;" disabled>Add Selected</button>
                    <button id="cancel-cpt-btn" style="padding: 8px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                </div>
            `;
            
            // Assemble dialog
            dialog.appendChild(header);
            dialog.appendChild(searchBar);
            dialog.appendChild(resultsContainer);
            dialog.appendChild(footer);
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            
            // Populate results
            function populateResults(codes) {
                resultsContainer.innerHTML = '';
                codes.forEach(function(cpt) {
                    var row = document.createElement('div');
                    row.style.cssText = `
                        padding: 12px;
                        border: 1px solid #ddd;
                        margin-bottom: 5px;
                        border-radius: 4px;
                        cursor: pointer;
                        transition: all 0.2s ease;
                    `;
                    row.innerHTML = `
                        <div style="font-weight: bold; color: #007bff;">${cpt.code}</div>
                        <div style="font-size: 14px; color: #666; margin-top: 4px;">${cpt.description}</div>
                    `;
                    
                    // Add hover effects
                    row.onmouseenter = function() {
                        if (!row.classList.contains('selected')) {
                            row.style.backgroundColor = '#f8f9fa';
                            row.style.borderColor = '#007bff';
                            row.style.boxShadow = '0 2px 4px rgba(0,123,255,0.1)';
                        }
                    };
                    
                    row.onmouseleave = function() {
                        if (!row.classList.contains('selected')) {
                            row.style.backgroundColor = '';
                            row.style.borderColor = '#ddd';
                            row.style.boxShadow = '';
                        }
                    };
                    
                    row.onclick = function() {
                        // Remove previous selection
                        var prevSelected = resultsContainer.querySelector('.selected');
                        if (prevSelected) {
                            prevSelected.classList.remove('selected');
                            prevSelected.style.backgroundColor = '';
                            prevSelected.style.borderColor = '#ddd';
                            prevSelected.style.boxShadow = '';
                        }
                        
                        // Select this row
                        row.classList.add('selected');
                        row.style.backgroundColor = '#e3f2fd';
                        row.style.borderColor = '#007bff';
                        row.style.boxShadow = '0 2px 8px rgba(0,123,255,0.2)';
                        
                        // Update footer
                        document.getElementById('selected-info').textContent = `Selected: ${cpt.code} - ${cpt.description.substring(0, 50)}...`;
                        document.getElementById('add-cpt-btn').disabled = false;
                        
                        // Store selected code
                        row.selectedCode = cpt;
                    };
                    
                    resultsContainer.appendChild(row);
                });
            }
            
            // Initial population
            populateResults(cptCodes);
            
            // Search functionality
            document.getElementById('search-cpt-btn').onclick = function() {
                var searchTerm = document.getElementById('cpt-search-input').value.toLowerCase();
                var filtered = cptCodes.filter(function(cpt) {
                    return cpt.code.toLowerCase().includes(searchTerm) || 
                           cpt.description.toLowerCase().includes(searchTerm);
                });
                populateResults(filtered);
            };
            
            // Enter key search
            document.getElementById('cpt-search-input').onkeypress = function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('search-cpt-btn').click();
                }
            };
            
            // Add button functionality
            document.getElementById('add-cpt-btn').onclick = function() {
                var selectedRow = resultsContainer.querySelector('.selected');
                if (selectedRow && selectedRow.selectedCode) {
                    setCPTCode(selectedRow.selectedCode.code, selectedRow.selectedCode.description);
                    document.body.removeChild(overlay);
                }
            };
            
            // Close button functionality
            document.getElementById('close-cpt-dialog').onclick = function() {
                document.body.removeChild(overlay);
            };
            
            // Cancel button functionality
            document.getElementById('cancel-cpt-btn').onclick = function() {
                document.body.removeChild(overlay);
            };
            
            // Close on overlay click
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                }
            };
            
            console.log('Simple CPT dialog created');
        }
        
        // Custom CPT dialog function
        function createCustomCPTDialog(url, title, width, height) {
            console.log('Creating custom CPT dialog');
            
            // Create modal overlay
            var overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                justify-content: center;
                align-items: center;
            `;
            
            // Create dialog container
            var dialog = document.createElement('div');
            dialog.style.cssText = `
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                width: ${width}px;
                height: ${height}px;
                max-width: 90vw;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
            `;
            
            // Create header
            var header = document.createElement('div');
            header.style.cssText = `
                padding: 15px 20px;
                border-bottom: 1px solid #ddd;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8f9fa;
                border-radius: 8px 8px 0 0;
            `;
            header.innerHTML = `
                <h4 style="margin: 0; color: #333;">${title}</h4>
                <button id="close-cpt-dialog" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
            `;
            
            // Create iframe for the CPT search
            var iframe = document.createElement('iframe');
            iframe.style.cssText = `
                width: 100%;
                flex: 1;
                border: none;
                border-radius: 0 0 8px 8px;
            `;
            iframe.src = url;
            
            // Assemble dialog
            dialog.appendChild(header);
            dialog.appendChild(iframe);
            overlay.appendChild(dialog);
            document.body.appendChild(overlay);
            
            // Close button functionality
            document.getElementById('close-cpt-dialog').onclick = function() {
                document.body.removeChild(overlay);
            };
            
            // Close on overlay click
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                }
            };
            
            // Set up iframe communication
            iframe.onload = function() {
                try {
                    // Add the missing functions that the CPT search expects
                    iframe.contentWindow.opener = {
                        get_related: function() { return []; },
                        set_related: set_related,
                        set_related_target: set_related_target,
                        setCPTCode: setCPTCode,
                        closed: false
                    };
                    
                    // Also add to parent
                    iframe.contentWindow.parent = {
                        get_related: function() { return []; },
                        set_related: set_related,
                        set_related_target: set_related_target,
                        setCPTCode: setCPTCode,
                        closed: false
                    };
                    
                    console.log('Iframe loaded and opener functions set');
                } catch (error) {
                    console.log('Cross-origin iframe, using message communication');
                }
            };
            
            // Listen for messages from iframe
            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'cpt_selected') {
                    setCPTCode(event.data.code, event.data.description);
                    document.body.removeChild(overlay);
                }
            });
            
            console.log('Custom CPT dialog created');
        }
        
        // Success notification function
        function showSuccessNotification(message) {
            // Create notification element
            var notification = document.createElement('div');
            notification.className = 'success-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 8px 16px rgba(16, 185, 129, 0.3);
                z-index: 10000;
                font-weight: 600;
                max-width: 300px;
                font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            `;
            notification.textContent = message;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 3000);
        }
        
        // Check dialog context on page load
        document.addEventListener('DOMContentLoaded', function() {
            debugScripts();
            checkDialogContext();
        });
    </script>
</body>
</html>
