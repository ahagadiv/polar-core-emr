<?php
// Test script to verify session configuration
session_start();

echo "<h2>POLAR Healthcare - Session Configuration Test</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Lifetime:</strong> " . ini_get('session.gc_maxlifetime') . " seconds (" . (ini_get('session.gc_maxlifetime')/3600) . " hours)</p>";
echo "<p><strong>Cookie Lifetime:</strong> " . ini_get('session.cookie_lifetime') . " seconds (" . (ini_get('session.cookie_lifetime')/3600) . " hours)</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Save Path:</strong> " . ini_get('session.save_path') . "</p>";

// Set a test session variable
if (!isset($_SESSION['test_time'])) {
    $_SESSION['test_time'] = date('Y-m-d H:i:s');
    echo "<p><strong>Session started at:</strong> " . $_SESSION['test_time'] . "</p>";
} else {
    echo "<p><strong>Session started at:</strong> " . $_SESSION['test_time'] . "</p>";
    echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>";
}

echo "<p><a href='test_session.php'>Refresh this page</a> - Your session should persist!</p>";
echo "<p><a href='interface/login/login.php'>Go to Login Page</a></p>";
?>



