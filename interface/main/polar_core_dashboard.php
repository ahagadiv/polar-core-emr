<?php
/**
 * POLAR Healthcare CORE Dashboard
 * Central dashboard for PICC mobile company workflow management
 */

require_once("../globals.php");
require_once("$srcdir/api.inc");

use OpenEMR\Common\Csrf\CsrfUtils;

// SECURITY: Ensure user is logged in
if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
    // Redirect to login if not authenticated
    header("Location: ../login/login.php");
    exit();
}

// Get dashboard data
$dashboard_data = getPICCDashboardData();

function getPICCDashboardData() {
    global $GLOBALS;
    
    $data = array();
    
    // Get patients by status
    $data['by_status'] = array();
    $statuses = array('picc_candidate', 'midline_candidate', 'requires_clearance', 'pending_payment', 'ready_schedule');
    
    foreach ($statuses as $status) {
        $sql = "SELECT COUNT(*) as count FROM form_picc_intake WHERE patient_status = ? AND activity = 1";
        $result = sqlQuery($sql, array($status));
        $data['by_status'][$status] = $result['count'];
    }
    
    // Get patients by priority
    $data['by_priority'] = array();
    $priorities = array('stat', 'urgent', 'routine');
    
    foreach ($priorities as $priority) {
        $sql = "SELECT COUNT(*) as count FROM form_picc_intake WHERE priority_level = ? AND activity = 1";
        $result = sqlQuery($sql, array($priority));
        $data['by_priority'][$priority] = $result['count'];
    }
    
    // Get recent STAT patients
    $sql = "SELECT f.*, p.fname, p.lname, p.pubpid 
            FROM form_picc_intake f 
            JOIN patient_data p ON f.pid = p.pid 
            WHERE f.priority_level = 'stat' AND f.activity = 1 
            ORDER BY f.date DESC 
            LIMIT 10";
    $data['stat_patients'] = sqlStatement($sql);
    
    // Get pending payments
    $sql = "SELECT f.*, p.fname, p.lname, p.pubpid 
            FROM form_picc_intake f 
            JOIN patient_data p ON f.pid = p.pid 
            WHERE f.payment_status IN ('pending', 'partial') AND f.activity = 1 
            ORDER BY f.date DESC 
            LIMIT 10";
    $data['pending_payments'] = sqlStatement($sql);
    
    return $data;
}

?>
<html>
<head>
    <title>POLAR CORE Dashboard</title>
    <link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 25%, #60a5fa 50%, #93c5fd 75%, #dbeafe 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: #2c3e50;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Apple-style floating particles */
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: float-particle 15s infinite linear;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.6), 0 0 30px rgba(45, 106, 227, 0.3);
        }
        
        .particle:nth-child(1) { width: 6px; height: 6px; left: 5%; animation-delay: 0s; animation-duration: 25s; }
        .particle:nth-child(2) { width: 8px; height: 8px; left: 15%; animation-delay: 2s; animation-duration: 22s; }
        .particle:nth-child(3) { width: 4px; height: 4px; left: 25%; animation-delay: 4s; animation-duration: 28s; }
        .particle:nth-child(4) { width: 7px; height: 7px; left: 35%; animation-delay: 6s; animation-duration: 20s; }
        .particle:nth-child(5) { width: 5px; height: 5px; left: 45%; animation-delay: 8s; animation-duration: 30s; }
        .particle:nth-child(6) { width: 9px; height: 9px; left: 55%; animation-delay: 10s; animation-duration: 24s; }
        .particle:nth-child(7) { width: 4px; height: 4px; left: 65%; animation-delay: 12s; animation-duration: 26s; }
        .particle:nth-child(8) { width: 6px; height: 6px; left: 75%; animation-delay: 14s; animation-duration: 21s; }
        .particle:nth-child(9) { width: 5px; height: 5px; left: 85%; animation-delay: 16s; animation-duration: 29s; }
        .particle:nth-child(10) { width: 8px; height: 8px; left: 10%; animation-delay: 18s; animation-duration: 23s; }
        .particle:nth-child(11) { width: 4px; height: 4px; left: 20%; animation-delay: 20s; animation-duration: 27s; }
        .particle:nth-child(12) { width: 6px; height: 6px; left: 30%; animation-delay: 22s; animation-duration: 19s; }
        .particle:nth-child(13) { width: 5px; height: 5px; left: 40%; animation-delay: 24s; animation-duration: 31s; }
        .particle:nth-child(14) { width: 7px; height: 7px; left: 50%; animation-delay: 26s; animation-duration: 25s; }
        .particle:nth-child(15) { width: 4px; height: 4px; left: 60%; animation-delay: 28s; animation-duration: 22s; }
        .particle:nth-child(16) { width: 6px; height: 6px; left: 70%; animation-delay: 30s; animation-duration: 28s; }
        .particle:nth-child(17) { width: 5px; height: 5px; left: 80%; animation-delay: 32s; animation-duration: 20s; }
        .particle:nth-child(18) { width: 8px; height: 8px; left: 90%; animation-delay: 34s; animation-duration: 26s; }
        .particle:nth-child(19) { width: 4px; height: 4px; left: 12%; animation-delay: 36s; animation-duration: 24s; }
        .particle:nth-child(20) { width: 6px; height: 6px; left: 22%; animation-delay: 38s; animation-duration: 27s; }
        .particle:nth-child(21) { width: 5px; height: 5px; left: 32%; animation-delay: 40s; animation-duration: 21s; }
        .particle:nth-child(22) { width: 7px; height: 7px; left: 42%; animation-delay: 42s; animation-duration: 29s; }
        .particle:nth-child(23) { width: 4px; height: 4px; left: 52%; animation-delay: 44s; animation-duration: 23s; }
        .particle:nth-child(24) { width: 6px; height: 6px; left: 62%; animation-delay: 46s; animation-duration: 26s; }
        .particle:nth-child(25) { width: 5px; height: 5px; left: 72%; animation-delay: 48s; animation-duration: 20s; }
        .particle:nth-child(26) { width: 8px; height: 8px; left: 82%; animation-delay: 50s; animation-duration: 28s; }
        .particle:nth-child(27) { width: 4px; height: 4px; left: 92%; animation-delay: 52s; animation-duration: 24s; }
        
        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateX(0px) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) translateX(50px) rotate(360deg);
                opacity: 0;
            }
        }
        
        /* DateTime Display Styles */
        .datetime-display {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .digital-clock {
            font-family: 'Courier New', monospace;
            font-size: 2.2em;
            font-weight: 700;
            color: white;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            letter-spacing: 2px;
        }
        
        .current-date {
            font-size: 1.1em;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #2D6AE3 0%, #1e4a8c 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(45, 106, 227, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(30deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(30deg); }
        }
        
        .dashboard-header h1 {
            margin: 0;
            font-size: 3.2em;
            font-weight: 700;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }
        
        .dashboard-header p {
            margin: 15px 0 0 0;
            font-size: 1.3em;
            opacity: 0.95;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-color), var(--accent-light));
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card.picc { 
            --accent-color: #2D6AE3; 
            --accent-light: #4a7ce8;
        }
        .stat-card.midline { 
            --accent-color: #2D6AE3; 
            --accent-light: #4a7ce8;
        }
        .stat-card.clearance { 
            --accent-color: #f39c12; 
            --accent-light: #f1c40f;
        }
        .stat-card.payment { 
            --accent-color: #e74c3c; 
            --accent-light: #ec7063;
        }
        .stat-card.ready { 
            --accent-color: #27ae60; 
            --accent-light: #2ecc71;
        }
        .stat-card.stat { 
            --accent-color: #e74c3c; 
            --accent-light: #ec7063;
        }
        .stat-card.urgent { 
            --accent-color: #f39c12; 
            --accent-light: #f1c40f;
        }
        .stat-card.routine { 
            --accent-color: #2D6AE3; 
            --accent-light: #4a7ce8;
        }
        
        .stat-number {
            font-size: 3.5em;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, var(--accent-color), var(--accent-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 1.1em;
            color: #636e72;
            margin: 10px 0 0 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            margin-bottom: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section h2 {
            color: #2d3436;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8em;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }
        
        .section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #2D6AE3, #4a7ce8);
            border-radius: 2px;
        }
        
        .patient-list {
            max-height: 450px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .patient-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .patient-list::-webkit-scrollbar-track {
            background: #f1f2f6;
            border-radius: 3px;
        }
        
        .patient-list::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #2D6AE3, #4a7ce8);
            border-radius: 3px;
        }
        
        .patient-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .patient-item:hover {
            transform: translateX(5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #2D6AE3;
        }
        
        .patient-info {
            flex: 1;
        }
        
        .patient-name {
            font-weight: 700;
            color: #2d3436;
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        
        .patient-mrn {
            color: #636e72;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 3px;
        }
        
        .patient-details {
            color: #2D6AE3;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .priority-badge, .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .priority-stat {
            background: linear-gradient(135deg, #d63031, #ff7675);
            color: white;
        }
        
        .priority-urgent {
            background: linear-gradient(135deg, #e17055, #fdcb6e);
            color: white;
        }
        
        .priority-routine {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
        }
        
        .status-ready {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2D6AE3, #4a7ce8);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #fdcb6e, #e17055);
            color: white;
        }
        
        .quick-actions {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .quick-actions .btn {
            min-width: 180px;
            padding: 15px 25px;
            font-size: 1em;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: linear-gradient(135deg, #2D6AE3, #4a7ce8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 70px;
            height: 70px;
            font-size: 1.8em;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(45, 106, 227, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1) rotate(180deg);
            box-shadow: 0 15px 40px rgba(45, 106, 227, 0.6);
        }
        
        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        @media (max-width: 1200px) {
            .grid-2-col {
                grid-template-columns: 1fr;
            }
        }
        
        .status-indicator {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2D6AE3;
            box-shadow: 0 0 0 3px rgba(45, 106, 227, 0.3);
        }
        
        .status-indicator.pending {
            background: #f39c12;
            box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.3);
        }
        
        .status-indicator.stat {
            background: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3); }
            50% { box-shadow: 0 0 0 8px rgba(231, 76, 60, 0.1); }
            100% { box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3); }
        }
    </style>
</head>
<body class="body_top">
    <!-- Apple-style floating particles -->
    <div class="particles-container">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>🏥 POLAR CORE ™ Tracker</h1>
            <p>POLAR | STAT Vascular Access - Workflow Management</p>
            <div class="datetime-display">
                <div class="digital-clock" id="digitalClock"></div>
                <div class="current-date" id="currentDate"></div>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="stats-grid">
            <div class="stat-card picc">
                <div class="stat-number"><?php echo $dashboard_data['by_status']['picc_candidate']; ?></div>
                <div class="stat-label">PICC Candidates</div>
            </div>
            <div class="stat-card midline">
                <div class="stat-number"><?php echo $dashboard_data['by_status']['midline_candidate']; ?></div>
                <div class="stat-label">MIDLINE Candidates</div>
            </div>
            <div class="stat-card clearance">
                <div class="stat-number"><?php echo $dashboard_data['by_status']['requires_clearance']; ?></div>
                <div class="stat-label">Require Clearance</div>
            </div>
            <div class="stat-card payment">
                <div class="stat-number"><?php echo $dashboard_data['by_status']['pending_payment']; ?></div>
                <div class="stat-label">Pending Payment</div>
            </div>
            <div class="stat-card ready">
                <div class="stat-number"><?php echo $dashboard_data['by_status']['ready_schedule']; ?></div>
                <div class="stat-label">Ready to Schedule</div>
            </div>
        </div>

        <!-- Priority Overview -->
        <div class="stats-grid">
            <div class="stat-card stat">
                <div class="stat-number"><?php echo $dashboard_data['by_priority']['stat']; ?></div>
                <div class="stat-label">STAT Priority</div>
            </div>
            <div class="stat-card urgent">
                <div class="stat-number"><?php echo $dashboard_data['by_priority']['urgent']; ?></div>
                <div class="stat-label">Urgent Priority</div>
            </div>
            <div class="stat-card routine">
                <div class="stat-number"><?php echo $dashboard_data['by_priority']['routine']; ?></div>
                <div class="stat-label">Routine Priority</div>
            </div>
        </div>

        <div class="grid-2-col">
            <!-- STAT Patients -->
            <div class="section">
                <h2>🚨 STAT Patients (Highest Priority)</h2>
                <div class="patient-list">
                    <?php while ($patient = sqlFetchArray($dashboard_data['stat_patients'])): ?>
                    <div class="patient-item">
                        <div class="status-indicator stat"></div>
                        <div class="patient-info">
                            <div class="patient-name"><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></div>
                            <div class="patient-mrn">MRN: <?php echo htmlspecialchars($patient['pubpid']); ?></div>
                            <div class="patient-details">
                                <?php echo htmlspecialchars($patient['referring_physician']); ?> | 
                                <?php echo date('M j, Y', strtotime($patient['date'])); ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <span class="priority-badge priority-stat">STAT</span>
                            <a href="patient_file/summary/demographics.php?pid=<?php echo $patient['pid']; ?>" class="btn btn-primary">View</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="section">
                <h2>💰 Pending Payments</h2>
                <div class="patient-list">
                    <?php while ($patient = sqlFetchArray($dashboard_data['pending_payments'])): ?>
                    <div class="patient-item">
                        <div class="status-indicator pending"></div>
                        <div class="patient-info">
                            <div class="patient-name"><?php echo htmlspecialchars($patient['fname'] . ' ' . $patient['lname']); ?></div>
                            <div class="patient-mrn">MRN: <?php echo htmlspecialchars($patient['pubpid']); ?></div>
                            <div class="patient-details">
                                $<?php echo number_format($patient['estimated_cost'], 2); ?> | 
                                <?php echo ucfirst($patient['payment_status']); ?>
                            </div>
                        </div>
                        <div class="action-buttons">
                            <span class="status-badge status-pending"><?php echo ucfirst($patient['payment_status']); ?></span>
                            <a href="patient_file/summary/demographics.php?pid=<?php echo $patient['pid']; ?>" class="btn btn-warning">Bill</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h2>⚡ Quick Actions</h2>
            <div class="quick-actions">
                <a href="patient_file/patient_file.php?pid=new" class="btn btn-primary">New Patient</a>
                <a href="patient_file/history/encounters.php" class="btn btn-success">View All Patients</a>
                <a href="billing/sl_eob.php" class="btn btn-warning">Billing Management</a>
                <a href="calendar/index.php" class="btn btn-primary">Schedule Calendar</a>
                <a href="forms/picc_intake_form.php" class="btn btn-success">PICC Intake Form</a>
            </div>
        </div>
    </div>

    <button class="refresh-btn" onclick="location.reload()" title="Refresh Dashboard">🔄</button>

    <script>
        // Real-time clock with Central Time
        function updateClock() {
            const now = new Date();
            
            // Convert to Central Time
            const centralTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Chicago"}));
            
            // Format time (HH:MM:SS)
            const hours = centralTime.getHours().toString().padStart(2, '0');
            const minutes = centralTime.getMinutes().toString().padStart(2, '0');
            const seconds = centralTime.getSeconds().toString().padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;
            
            // Format date (MM-DD-YYYY)
            const month = (centralTime.getMonth() + 1).toString().padStart(2, '0');
            const day = centralTime.getDate().toString().padStart(2, '0');
            const year = centralTime.getFullYear();
            const dateString = `${month}-${day}-${year}`;
            
            // Format day of week
            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const dayOfWeek = days[centralTime.getDay()];
            
            // Update the display
            document.getElementById('digitalClock').textContent = timeString;
            document.getElementById('currentDate').textContent = `${dayOfWeek} - ${dateString}`;
        }
        
        // Update clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);
        
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
