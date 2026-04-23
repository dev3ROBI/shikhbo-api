<?php
/**
 * Shikhbo Admin Panel - Security Module
 * High-security: CSRF Protection, Rate Limiting, Security Headers
 */

// =======================
// SECURE SESSION CONFIG
// =======================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================
// REGENERATE SESSION ID
// =======================
if (!isset($_SESSION['_created'])) {
    $_SESSION['_created'] = time();
} elseif (time() - $_SESSION['_created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['_created'] = time();
}

// =======================
// SECURITY HEADERS
// =======================
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; img-src 'self' data: https://ui-avatars.com https:; font-src 'self' https://cdnjs.cloudflare.com; connect-src 'self'; frame-ancestors 'none';");

// =======================
// CSRF TOKEN MANAGEMENT
// =======================
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// =======================
// RATE LIMITING (MySQL)
// =======================
function checkRateLimit($mysqli, $email, $maxAttempts = 5, $lockoutMinutes = 15) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$lockoutMinutes} minutes"));

    $stmt = $mysqli->prepare(
        "SELECT COUNT(*) as attempts FROM login_attempts 
         WHERE (email = ? OR ip_address = ?) 
         AND success = 0 
         AND attempt_time > ?"
    );
    $stmt->bind_param('sss', $email, $ip, $cutoff);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result['attempts'] < $maxAttempts;
}

function logLoginAttempt($mysqli, $email, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $mysqli->prepare(
        "INSERT INTO login_attempts (email, ip_address, success, attempt_time) 
         VALUES (?, ?, ?, NOW())"
    );
    $stmt->bind_param('ssi', $email, $ip, $success);
    $stmt->execute();
    $stmt->close();
}

// =======================
// INPUT SANITIZATION
// =======================
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}