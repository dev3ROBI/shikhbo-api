<?php
// config.php

// Database Configuration
define('DB_HOST', 'mysql.railway.internal');
define('DB_NAME', 'shikbo');
define('DB_USER', 'robi');
define('DB_PASS', 'Nafia2Naoshin');

// Google OAuth2 Configuration - BOTH CLIENT IDs
define('GOOGLE_CLIENT_ID_WEB', '151985259285-nvemiiq9gg5lh7ap27vcrv25jv930ddm.apps.googleusercontent.com');     // Web Client
define('GOOGLE_CLIENT_ID_ANDROID', '151985259285-9vp42do9jbkl0gv5rv25hhi3u74t7sp9.apps.googleusercontent.com'); // Android Client

// JWT Secret Key 
define('JWT_SECRET', '?j=EaT(6LHCV]C=B6[E_EaA4HD)D7e2zRQ7S|XPxFRR[FFEXmo`;~uA>CJ_3MCA');

// CORS settings
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Error reporting (enable for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
