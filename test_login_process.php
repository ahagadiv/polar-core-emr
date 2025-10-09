<?php
/**
 * Test Login Process - Debug what happens during login
 */

// Include OpenEMR globals
require_once(__DIR__ . '/interface/globals.php');

echo "<h2>Login Process Test</h2>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check if this is a login attempt
if (isset($_POST['authUser']) && isset($_POST['clearPass'])) {
    echo "<h3>Login Attempt Detected</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($_POST['authUser']) . "</p>";
    
    // Check session before login
    echo "<h4>Session Before Login:</h4>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Session Variables:</strong></p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Simulate the login process
    $username = $_POST['authUser'];
    $password = $_POST['clearPass'];
    
    // Check if user exists
    $user = sqlQueryNoLog("SELECT * FROM users WHERE username = ? AND active = 1", [$username]);
    if ($user) {
        echo "<p>✅ User found in database</p>";
        
        // Check password
        $userSecure = sqlQueryNoLog("SELECT * FROM users_secure WHERE username = ?", [$username]);
        if ($userSecure && password_verify($password, $userSecure['password'])) {
            echo "<p>✅ Password verified</p>";
            
            // Set session variables manually
            $_SESSION['authUser'] = $username;
            $_SESSION['authUserID'] = $user['id'];
            $_SESSION['authPass'] = $userSecure['password'];
            $_SESSION['authProvider'] = 'Default';
            
            echo "<h4>Session After Manual Login:</h4>";
            echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
            echo "<p><strong>Session Variables:</strong></p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            // Test authCheckSession
            if (class_exists('OpenEMR\Common\Auth\AuthUtils')) {
                $authResult = \OpenEMR\Common\Auth\AuthUtils::authCheckSession();
                echo "<p><strong>authCheckSession() result:</strong> " . ($authResult ? '✅ TRUE' : '❌ FALSE') . "</p>";
            }
            
        } else {
            echo "<p>❌ Password verification failed</p>";
        }
    } else {
        echo "<p>❌ User not found or inactive</p>";
    }
} else {
    echo "<h3>Current Session Status</h3>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Session Variables:</strong></p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Show login form
    echo "<h3>Test Login Form</h3>";
    echo "<form method='POST'>";
    echo "<p>Username: <input type='text' name='authUser' value='polaradmin2025'></p>";
    echo "<p>Password: <input type='password' name='clearPass' value='Fourth747623!'></p>";
    echo "<p><input type='submit' value='Test Login'></p>";
    echo "</form>";
}

echo "<hr>";
echo "<p><a href='test_login_process.php'>🔄 Refresh this page</a></p>";
echo "<p><a href='debug_session_detailed.php'>🔍 Detailed Session Debug</a></p>";
echo "<p><a href='interface/login/login.php'>🔐 Real Login Page</a></p>";
?>



