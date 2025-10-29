<?php
/**
 * POLAR Healthcare - Get Appointment Data
 * Handles AJAX requests to get appointment data for the popup modal
 */

// Suppress all errors and warnings
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 0);
ob_start();

// Set JSON header immediately
header('Content-Type: application/json');

try {
    // Include OpenEMR files
    $ignoreAuth = false;
    require_once("../../globals.php");
    require_once("$srcdir/sql.inc");
    require_once("$srcdir/formatting.inc.php");

    // Check if user is authenticated
    if (!isset($_SESSION['authUserID']) || !$_SESSION['authUserID']) {
        throw new Exception('User not authenticated');
    }

    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get appointment ID
    $eid = intval($_POST['eid'] ?? 0);
    $pid = intval($_POST['pid'] ?? 0);

    if ($eid <= 0) {
        throw new Exception('Invalid appointment ID');
    }

    // Get appointment data
    $appointmentQuery = "SELECT 
        pc_eid, pc_pid, pc_eventDate, pc_startTime, pc_endTime, pc_duration, 
        pc_apptstatus, pc_title, pc_hometext, pc_facility, pc_billing_location, 
        pc_room, pc_catid, pc_aid, pc_informant 
        FROM openemr_postcalendar_events 
        WHERE pc_eid = ? AND pc_recurrtype = 0";
    
    $appointmentData = sqlQuery($appointmentQuery, [$eid]);
    
    if (!$appointmentData) {
        throw new Exception('Appointment not found');
    }

    // Get patient data if PID is provided
    $patientData = null;
    if ($pid > 0) {
        $patientQuery = "SELECT fname, lname, mname, sex, DOB, phone_home, phone_cell, street, city, state, postal_code, country FROM patient_data WHERE pid = ?";
        $patientData = sqlQuery($patientQuery, [$pid]);
    }

    // Get provider data
    $providerData = null;
    if ($appointmentData['pc_aid']) {
        $providerQuery = "SELECT fname, lname, mname, title, organization FROM users WHERE id = ?";
        $providerData = sqlQuery($providerQuery, [$appointmentData['pc_aid']]);
    }

    // Get category data
    $categoryData = null;
    if ($appointmentData['pc_catid']) {
        $categoryQuery = "SELECT pc_catname, pc_catdesc FROM openemr_postcalendar_categories WHERE pc_catid = ?";
        $categoryData = sqlQuery($categoryQuery, [$appointmentData['pc_catid']]);
    }

    // Get facility data
    $facilityData = null;
    if ($appointmentData['pc_facility']) {
        $facilityQuery = "SELECT name, phone, street, city, state, postal_code FROM facility WHERE id = ?";
        $facilityData = sqlQuery($facilityQuery, [$appointmentData['pc_facility']]);
    }

    // Format time
    $formattedStartTime = date('g:i A', strtotime($appointmentData['pc_startTime']));
    $formattedEndTime = date('g:i A', strtotime($appointmentData['pc_endTime']));
    $duration = $appointmentData['pc_duration'] ? $appointmentData['pc_duration'] . ' min' : '';

    // Calculate patient age
    $patientAge = '';
    if ($patientData && $patientData['DOB']) {
        $dob = new DateTime($patientData['DOB']);
        $now = new DateTime();
        $age = $now->diff($dob);
        $patientAge = $age->y . ' years';
    }

    // Format patient gender
    $patientGender = '';
    if ($patientData && $patientData['sex']) {
        $patientGender = ucfirst($patientData['sex']);
    }

    // Format patient phone
    $patientPhone = '';
    if ($patientData) {
        if ($patientData['phone_cell']) {
            $patientPhone = $patientData['phone_cell'];
        } elseif ($patientData['phone_home']) {
            $patientPhone = $patientData['phone_home'];
        }
    }

    // Format patient address
    $patientAddress = '';
    if ($patientData) {
        $addressParts = array_filter([
            $patientData['street'],
            $patientData['city'],
            $patientData['state'],
            $patientData['postal_code']
        ]);
        $patientAddress = implode(', ', $addressParts);
    }

    // Format provider name
    $providerName = '';
    $providerTitle = '';
    if ($providerData) {
        $providerName = trim($providerData['fname'] . ' ' . $providerData['lname']);
        $providerTitle = $providerData['title'] ?: '';
    }

    // Format service name
    $serviceName = '';
    if ($categoryData) {
        $serviceName = $categoryData['pc_catname'];
    } elseif ($appointmentData['pc_title']) {
        $serviceName = $appointmentData['pc_title'];
    }

    // Format service location
    $serviceLocation = '';
    if ($facilityData) {
        $serviceLocation = $facilityData['name'];
    } elseif ($appointmentData['pc_room']) {
        $serviceLocation = 'Room ' . $appointmentData['pc_room'];
    }

    // Build response
    $response = [
        'success' => true,
        'appointment' => [
            'eid' => $appointmentData['pc_eid'],
            'pid' => $appointmentData['pc_pid'],
            'time' => $formattedStartTime . ' - ' . $formattedEndTime,
            'duration' => $duration,
            'status' => $appointmentData['pc_apptstatus'] ?: 'S',
            'patientName' => $patientData ? trim($patientData['fname'] . ' ' . $patientData['lname']) : 'Unknown Patient',
            'patientGender' => $patientGender,
            'patientAge' => $patientAge,
            'patientDob' => $patientData ? oeFormatShortDate($patientData['DOB']) : '',
            'patientPhone' => $patientPhone,
            'patientAddress' => $patientAddress,
            'providerName' => $providerName,
            'providerTitle' => $providerTitle,
            'serviceName' => $serviceName,
            'serviceLocation' => $serviceLocation,
            'comments' => $appointmentData['pc_hometext'] ?: ''
        ]
    ];

    // Clean output buffer and send response
    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    // Clean output buffer and send error
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>