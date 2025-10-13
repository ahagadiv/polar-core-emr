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
        echo $twig->getTwig()->render('patient/card/patient_procedures.html.twig', [
            'patient_procedures' => $procedures,
            'patient' => $patient_data,
            'pid' => $patient_id,
            'csrf_token_form' => CsrfUtils::csrfGetToken(),
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
}
