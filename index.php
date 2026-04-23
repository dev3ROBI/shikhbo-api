<?php
header("Content-Type: application/json; charset=UTF-8");

echo json_encode([
    "status" => "success",
    "message" => "Shikhbo API is running successfully 🚀",
    "version" => "1.0",
    "endpoints" => [
        "login" => "api/login.php",
        "signup" => "api/signup.php",
        "google_login" => "api/google_login.php",
        "get_app_settings" => "api/get_app_settings.php"
    ],
    "server_time" => date("Y-m-d H:i:s")
]);
