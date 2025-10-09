<?php
/**
 * Simple Session Test - No OpenEMR dependencies
 */

session_start();

echo "<h2>Simple Session Test</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";

// Set a test variable
if (!isset($_SESSION['test_var'])) {
    $_SESSION['test_var'] = 'test_value_' . time();
    echo "<p>✅ Set test session variable: " . $_SESSION['test_var'] . "</p>";
} else {
    echo "<p>✅ Test session variable exists: " . $_SESSION['test_var'] . "</p>";
}

echo "<p><strong>All Session Variables:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='simple_session_test.php'>🔄 Refresh this page</a></p>";
echo "<p><a href='debug_session.php'>🔍 Go to OpenEMR Debug</a></p>";
?>



