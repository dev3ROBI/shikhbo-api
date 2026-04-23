<?php

// =======================
// DATABASE CONFIG
// =======================
define('DB_HOST', 'shortline.proxy.rlwy.net');
define('DB_NAME', 'railway');
define('DB_USER', 'root');
define('DB_PASS', 'fjEvGLOytnFmjDPIOXbVtrpLMIGotaFv');
define('DB_PORT', 14989);

// =======================
// GOOGLE OAUTH
// =======================
define('GOOGLE_CLIENT_ID_WEB', '151985259285-nvemiiq9gg5lh7ap27vcrv25jv930ddm.apps.googleusercontent.com');
define('GOOGLE_CLIENT_ID_ANDROID', '151985259285-9vp42do9jbkl0gv5rv25hhi3u74t7sp9.apps.googleusercontent.com');

// =======================
// JWT SECRET
// =======================
define('JWT_SECRET', '?j=EaT(6LHCV]C=B6[E_EaA4HD)D7e2zRQ7S|XPxFRR[FFEXmo`;~uA>CJ_3MCA');

// =======================
// CORS
// =======================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// =======================
// ERROR REPORTING
// =======================
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
