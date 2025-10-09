/**
 * POLAR Healthcare Session Keep-Alive
 * Prevents session timeouts during active use
 */

(function() {
    'use strict';
    
    let keepAliveInterval;
    let lastActivity = Date.now();
    let isActive = true;
    
    // Configuration
    const KEEP_ALIVE_INTERVAL = 5 * 60 * 1000; // 5 minutes
    const ACTIVITY_TIMEOUT = 10 * 60 * 1000; // 10 minutes
    const KEEP_ALIVE_URL = '/interface/main/session_keepalive.php';
    
    // Track user activity
    function updateActivity() {
        lastActivity = Date.now();
        isActive = true;
    }
    
    // Send keep-alive request
    function sendKeepAlive() {
        if (!isActive) {
            return;
        }
        
        fetch(KEEP_ALIVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=keepalive&timestamp=' + Date.now()
        })
        .then(response => {
            if (response.ok) {
                console.log('POLAR Healthcare: Session keep-alive successful');
            } else {
                console.warn('POLAR Healthcare: Session keep-alive failed:', response.status);
            }
        })
        .catch(error => {
            console.error('POLAR Healthcare: Session keep-alive error:', error);
        });
    }
    
    // Check if user is still active
    function checkActivity() {
        const now = Date.now();
        const timeSinceActivity = now - lastActivity;
        
        if (timeSinceActivity > ACTIVITY_TIMEOUT) {
            isActive = false;
            console.log('POLAR Healthcare: User inactive, stopping keep-alive');
            clearInterval(keepAliveInterval);
        }
    }
    
    // Initialize keep-alive system
    function initKeepAlive() {
        // Only run on main interface pages
        if (window.location.pathname.includes('/interface/main/') || 
            window.location.pathname.includes('/interface/patient_file/') ||
            window.location.pathname.includes('/interface/forms/')) {
            
            console.log('POLAR Healthcare: Initializing session keep-alive');
            
            // Set up activity tracking
            const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
            events.forEach(event => {
                document.addEventListener(event, updateActivity, true);
            });
            
            // Start keep-alive interval
            keepAliveInterval = setInterval(() => {
                checkActivity();
                if (isActive) {
                    sendKeepAlive();
                }
            }, KEEP_ALIVE_INTERVAL);
            
            // Initial keep-alive
            sendKeepAlive();
        }
    }
    
    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initKeepAlive);
    } else {
        initKeepAlive();
    }
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (keepAliveInterval) {
            clearInterval(keepAliveInterval);
        }
    });
    
})();



