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

class C_Procedure extends Controller
{
    public $procedures;
    public $patient_id;

    public function __construct($template_mod = "general")
    {
        parent::__construct();
        $this->procedures = [];
        $this->patient_id = null;
    }

    public function list()
    {
        $patient_id = $_GET['id'] ?? null;
        
        if (!$patient_id) {
            echo "Error: No patient ID provided";
            return;
        }

        $this->patient_id = $patient_id;
        
        // Check ACL permissions
        if (!AclMain::aclCheckCore('patients', 'proc', '', 'read')) {
            echo "Access denied";
            return;
        }

        // Get procedures for this patient
        $this->getProcedures($patient_id);
        
        // Render the procedures list
        $this->renderProceduresList();
    }

    private function getProcedures($patient_id)
    {
        // Query the patient_procedures table
        $sql = "SELECT * FROM patient_procedures WHERE patient_id = ? ORDER BY procedure_date DESC";
        $result = sqlStatement($sql, [$patient_id]);
        
        $this->procedures = [];
        while ($row = sqlFetchArray($result)) {
            $this->procedures[] = $row;
        }
    }

    private function renderProceduresList()
    {
        echo "<div class='container-fluid'>";
        echo "<h3>Patient Procedures</h3>";
        
        if (empty($this->procedures)) {
            echo "<p>No procedures found for this patient.</p>";
        } else {
            echo "<table class='table table-striped'>";
            echo "<thead><tr>";
            echo "<th>CPT Code</th>";
            echo "<th>Description</th>";
            echo "<th>Date</th>";
            echo "<th>Status</th>";
            echo "<th>Actions</th>";
            echo "</tr></thead>";
            echo "<tbody>";
            
            foreach ($this->procedures as $procedure) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($procedure['cpt_code']) . "</td>";
                echo "<td>" . htmlspecialchars($procedure['procedure_description']) . "</td>";
                echo "<td>" . htmlspecialchars($procedure['procedure_date']) . "</td>";
                echo "<td>" . htmlspecialchars($procedure['status']) . "</td>";
                echo "<td>";
                echo "<a href='#' class='btn btn-sm btn-primary'>Edit</a> ";
                echo "<a href='#' class='btn btn-sm btn-danger'>Delete</a>";
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
        }
        
        echo "<div class='mt-3'>";
        echo "<a href='#' class='btn btn-primary'>Add New Procedure</a>";
        echo "</div>";
        
        echo "</div>";
    }
}
