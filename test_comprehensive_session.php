<?php
/**
 * POLAR Healthcare Comprehensive Session Test
 * Tests all aspects of session persistence
 */

session_start();

echo "<h2>POLAR Healthcare - Comprehensive Session Test</h2>";
echo "<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Basic PHP Session
echo "<h3>Test 1: Basic PHP Session</h3>";
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    $_SESSION['test_start'] = time();
    echo "<p>✅ New PHP session started</p>";
} else {
    $_SESSION['test_counter']++;
    $duration = time() - $_SESSION['test_start'];
    echo "<p>✅ PHP session active - Counter: " . $_SESSION['test_counter'] . " (Duration: {$duration}s)</p>";
}

// Test 2: OpenEMR Session Variables
echo "<h3>Test 2: OpenEMR Session Variables</h3>";
if (isset($_SESSION['authUser'])) {
    echo "<p>✅ OpenEMR session active</p>";
    echo "<p><strong>User:</strong> " . htmlspecialchars($_SESSION['authUser']) . "</p>";
    echo "<p><strong>User ID:</strong> " . htmlspecialchars($_SESSION['authUserID']) . "</p>";
    echo "<p><strong>Provider:</strong> " . htmlspecialchars($_SESSION['authProvider']) . "</p>";
    
    // Test 3: Session Tracker
    echo "<h3>Test 3: Session Tracker</h3>";
    if (isset($_SESSION['session_database_uuid'])) {
        echo "<p>✅ Session tracker UUID present: " . substr($_SESSION['session_database_uuid'], 0, 8) . "...</p>";
        
        // Check database
        try {
            $result = sqlQueryNoLog("SELECT `last_updated`, NOW() as `current_time` FROM `session_tracker` WHERE `uuid` = ?", [$_SESSION['session_database_uuid']]);
            if ($result) {
                $last_updated = strtotime($result['last_updated']);
                $current_time = strtotime($result['current_time']);
                $time_diff = $current_time - $last_updated;
                echo "<p>✅ Session tracker in database - Last updated: " . $result['last_updated'] . " ({$time_diff}s ago)</p>";
                
                // Check if session would be expired
                $timeout = $GLOBALS['timeout'] ?? 28800;
                $grace_period = 300;
                $total_timeout = $timeout + $grace_period;
                
                if ($time_diff > $total_timeout) {
                    echo "<p>❌ Session would be expired (timeout: {$timeout}s + grace: {$grace_period}s = {$total_timeout}s)</p>";
                } else {
                    echo "<p>✅ Session would be valid (within {$total_timeout}s timeout)</p>";
                }
            } else {
                echo "<p>❌ No session tracker entry in database</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p>❌ No session tracker UUID</p>";
    }
} else {
    echo "<p>❌ No OpenEMR session - <a href='interface/login/login.php'>Please log in first</a></p>";
}

// Test 4: Session Configuration
echo "<h3>Test 4: Session Configuration</h3>";
echo "<p><strong>PHP Session Lifetime:</strong> " . ini_get('session.gc_maxlifetime') . "s (" . (ini_get('session.gc_maxlifetime')/3600) . "h)</p>";
echo "<p><strong>Cookie Lifetime:</strong> " . ini_get('session.cookie_lifetime') . "s (" . (ini_get('session.cookie_lifetime')/3600) . "h)</p>";
echo "<p><strong>OpenEMR Timeout:</strong> " . ($GLOBALS['timeout'] ?? 'Not set') . "s</p>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";

// Test 5: Keep-Alive Test
echo "<h3>Test 5: Keep-Alive System</h3>";
echo "<p><a href='#' onclick='testKeepAlive(); return false;'>🔄 Test Keep-Alive</a></p>";
echo "<div id='keepalive-result'></div>";

// JavaScript for keep-alive test
echo "<script>
function testKeepAlive() {
    const resultDiv = document.getElementById('keepalive-result');
    resultDiv.innerHTML = '<p>Testing keep-alive...</p>';
    
    fetch('/interface/main/session_keepalive.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'action=keepalive&timestamp=' + Date.now()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<p>✅ Keep-alive successful: ' + data.message + '</p>';
        } else {
            resultDiv.innerHTML = '<p>❌ Keep-alive failed: ' + (data.error || 'Unknown error') + '</p>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<p>❌ Keep-alive error: ' + error.message + '</p>';
    });
}
</script>";

// Navigation
echo "<hr>";
echo "<p><a href='test_comprehensive_session.php'>🔄 Refresh this test</a></p>";
echo "<p><a href='interface/login/login.php'>🔐 Login Page</a></p>";
echo "<p><a href='interface/main/main_screen.php'>🏥 Main Dashboard</a></p>";
?>



