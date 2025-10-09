<?php
// Test script to verify OpenEMR session persistence
session_start();

echo "<h2>POLAR Healthcare - Session Persistence Test</h2>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check if we have OpenEMR session variables
if (isset($_SESSION['authUser'])) {
    echo "<p><strong>✅ OpenEMR Session Active</strong></p>";
    echo "<p><strong>User:</strong> " . htmlspecialchars($_SESSION['authUser']) . "</p>";
    echo "<p><strong>User ID:</strong> " . htmlspecialchars($_SESSION['authUserID']) . "</p>";
    echo "<p><strong>Provider:</strong> " . htmlspecialchars($_SESSION['authProvider']) . "</p>";
    
    if (isset($_SESSION['session_database_uuid'])) {
        echo "<p><strong>Session UUID:</strong> " . htmlspecialchars($_SESSION['session_database_uuid']) . "</p>";
    }
} else {
    echo "<p><strong>❌ No OpenEMR Session Found</strong></p>";
    echo "<p>You need to log in first at: <a href='interface/login/login.php'>Login Page</a></p>";
}

echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Lifetime:</strong> " . ini_get('session.gc_maxlifetime') . " seconds (" . (ini_get('session.gc_maxlifetime')/3600) . " hours)</p>";

// Test session persistence
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    $_SESSION['test_start_time'] = date('Y-m-d H:i:s');
    echo "<p><strong>🆕 New test session started</strong></p>";
} else {
    $_SESSION['test_counter']++;
    echo "<p><strong>🔄 Session refreshed " . $_SESSION['test_counter'] . " times</strong></p>";
    echo "<p><strong>Test started at:</strong> " . $_SESSION['test_start_time'] . "</p>";
}

echo "<p><a href='test_session_persistence.php'>🔄 Refresh this page</a> - Counter should increment!</p>";
echo "<p><a href='interface/login/login.php'>🔐 Go to Login Page</a></p>";
echo "<p><a href='interface/main/main_screen.php'>🏥 Go to Main Dashboard</a></p>";
?>



