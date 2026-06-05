<?php

// =======================
// DATABASE CONFIG (TiDB)
// =======================
define('DB_HOST', 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com');
define('DB_NAME', 'shikhbo');
define('DB_USER', '21JC5TLVg9DHH77.root');
define('DB_PASS', 'Ahhx4zufux1iAfC6'); // rotate this immediately
define('DB_PORT', 4000);

// CA CERT (IMPORTANT for TiDB)
define('DB_SSL_CA', __DIR__ . '/ca.pem');

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
