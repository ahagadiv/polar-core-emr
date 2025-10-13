<?php

/**
 * C_Procedure.class.php
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    POLAR Healthcare
 * @copyright Copyright (c) 2024 POLAR Healthcare
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once(__DIR__ . "/../library/forms.inc.php");
require_once(__DIR__ . "/../library/patient.inc.php");

use OpenEMR\Common\Acl\AclMain;
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\Twig\TwigContainer;

class C_Procedure extends Controller
{
    public function list_action()
    {
        $patient_id = $_GET['id'] ?? null;
        
        if (!$patient_id) {
            echo "Error: No patient ID provided";
            return;
        }
        
        // Check ACL permissions
        if (!AclMain::aclCheckCore('patients', 'proc', '', 'read')) {
            echo "Access denied";
            return;
        }

        // Get procedures for this patient
        $procedures = $this->getProcedures($patient_id);
        
        // Get patient data
        $patient_data = $this->getPatientData($patient_id);
        
        // Render using Twig template
        $twig = new TwigContainer(null, $GLOBALS['kernel']);
        echo $twig->getTwig()->render('patient/procedures_full_page.html.twig', [
            'patient_procedures' => $procedures,
            'patient' => $patient_data,
            'pid' => $patient_id,
            'webroot' => $GLOBALS['webroot'],
            'btnLink' => $GLOBALS['webroot'] . '/controller.php?procedure&add&id=' . $patient_id,
            'btnLabel' => 'Add New Procedure',
            'csrf_token_form' => CsrfUtils::collectCsrfToken(),
        ]);
    }

    private function getProcedures($patient_id)
    {
        // Query the patient_procedures table
        $sql = "SELECT * FROM patient_procedures WHERE patient_id = ? ORDER BY procedure_date DESC";
        $result = sqlStatement($sql, [$patient_id]);
        
        $procedures = [];
        while ($row = sqlFetchArray($result)) {
            $procedures[] = $row;
        }
        
        return $procedures;
    }
    
    private function getPatientData($patient_id)
    {
        // Get basic patient data
        $sql = "SELECT fname, lname, pubpid FROM patient_data WHERE pid = ?";
        $result = sqlQuery($sql, [$patient_id]);
        return $result ?: [];
    }
    
    public function add_action()
    {
        $patient_id = $_GET['id'] ?? null;
        
        if (!$patient_id) {
            echo "Error: No patient ID provided";
            return;
        }
        
        // Check ACL permissions
        if (!AclMain::aclCheckCore('patients', 'proc', '', 'write')) {
            echo "Access denied";
            return;
        }
        
        // Get patient data
        $patient_data = $this->getPatientData($patient_id);
        
        // Render add procedure form
        $twig = new TwigContainer(null, $GLOBALS['kernel']);
        echo $twig->getTwig()->render('patient/add_procedure_full_form.html.twig', [
            'patient' => $patient_data,
            'pid' => $patient_id,
            'webroot' => $GLOBALS['webroot'],
            'csrf_token_form' => CsrfUtils::collectCsrfToken(),
        ]);
    }
    
    public function edit_action()
    {
        $patient_id = $_GET['id'] ?? null;
        $procedure_id = $_GET['proc_id'] ?? null;
        
        if (!$patient_id || !$procedure_id) {
            echo "Error: Missing patient ID or procedure ID";
            return;
        }
        
        // Check ACL permissions
        if (!AclMain::aclCheckCore('patients', 'proc', '', 'write')) {
            echo "Access denied";
            return;
        }
        
        // Get procedure data
        $procedure = $this->getProcedureById($procedure_id);
        if (!$procedure) {
            echo "Error: Procedure not found";
            return;
        }
        
        // Get patient data
        $patient_data = $this->getPatientData($patient_id);
        
        // Render edit procedure form
        $twig = new TwigContainer(null, $GLOBALS['kernel']);
        echo $twig->getTwig()->render('patient/edit_procedure_form.html.twig', [
            'patient' => $patient_data,
            'procedure' => $procedure,
            'pid' => $patient_id,
            'webroot' => $GLOBALS['webroot'],
            'csrf_token_form' => CsrfUtils::collectCsrfToken(),
        ]);
    }
    
    public function save_action()
    {
        $patient_id = $_POST['pid'] ?? null;
        $procedure_id = $_POST['procedure_id'] ?? null;
        
        if (!$patient_id) {
            echo "Error: No patient ID provided";
            return;
        }
        
        // Check ACL permissions
        if (!AclMain::aclCheckCore('patients', 'proc', '', 'write')) {
            echo "Access denied";
            return;
        }
        
        // Verify CSRF token
        if (!CsrfUtils::verifyCsrfToken($_POST['csrf_token_form'])) {
            echo "Error: Invalid CSRF token";
            return;
        }
        
        // Get form data
        $date = $_POST['date'] ?? '';
        $cpt_code = $_POST['cpt_code'] ?? '';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'ACTIVE';
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if (empty($date) || empty($cpt_code) || empty($description)) {
            echo "Error: Required fields missing";
            return;
        }
        
        // Convert date format
        $date = date('Y-m-d H:i:s', strtotime($date));
        
        if ($procedure_id) {
            // Update existing procedure
            $sql = "UPDATE patient_procedures SET procedure_date = ?, cpt_code = ?, procedure_description = ?, status = ?, comments = ? WHERE id = ? AND patient_id = ?";
            sqlStatement($sql, [$date, $cpt_code, $description, $status, $notes, $procedure_id, $patient_id]);
        } else {
            // Insert new procedure
            $sql = "INSERT INTO patient_procedures (patient_id, procedure_date, cpt_code, procedure_description, status, comments, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            sqlStatement($sql, [$patient_id, $date, $cpt_code, $description, $status, $notes, $_SESSION['authUser'] ?? 'system']);
        }
        
        // Redirect back to procedures list
        header("Location: " . $GLOBALS['webroot'] . "/controller.php?procedure&list&id=" . $patient_id);
        exit;
    }
    
    public function delete_action()
    {
        $patient_id = $_GET['id'] ?? null;
        $procedure_id = $_GET['proc_id'] ?? null;
        
        if (!$patient_id || !$procedure_id) {
            echo "Error: Missing patient ID or procedure ID";
            return;
        }
        
        // Check ACL permissions
        if (!AclMain::aclCheckCore('patients', 'proc', '', 'write')) {
            echo "Access denied";
            return;
        }
        
        // Delete procedure
        $sql = "DELETE FROM patient_procedures WHERE id = ? AND patient_id = ?";
        sqlStatement($sql, [$procedure_id, $patient_id]);
        
        // Redirect back to procedures list
        header("Location: " . $GLOBALS['webroot'] . "/controller.php?procedure&list&id=" . $patient_id);
        exit;
    }
    
    private function getProcedureById($procedure_id)
    {
        $sql = "SELECT * FROM patient_procedures WHERE id = ?";
        $result = sqlQuery($sql, [$procedure_id]);
        return $result ?: null;
    }
}
