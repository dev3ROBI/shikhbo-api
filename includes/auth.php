<?php
/**
 * Shikhbo Admin Panel - Authentication Module
 * Role-Based Access Control (RBAC) for Admin Panel
 */

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/security.php';

// =======================
// DATABASE CONNECTION
// =======================
function getDBConnection() {
    static $mysqli = null;
    if ($mysqli === null) {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($mysqli->connect_error) {
            die("Database connection failed: " . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
    }
    return $mysqli;
}

// =======================
// CHECK ADMIN LOGIN
// =======================
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && 
           isset($_SESSION['admin_role']) && 
           $_SESSION['admin_role'] === 'admin' &&
           isset($_SESSION['admin_last_activity']) &&
           (time() - $_SESSION['admin_last_activity'] < 1800);
}

// =======================
// REQUIRE ADMIN AUTH
// =======================
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /pages/admin_login.php');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
}

// =======================
// AUTHENTICATE ADMIN
// =======================
function authenticateAdmin($email, $password) {
    $mysqli = getDBConnection();

    if (!checkRateLimit($mysqli, $email)) {
        return ['status' => 'error', 'message' => 'Too many login attempts. Please try again in 15 minutes.'];
    }

    $stmt = $mysqli->prepare(
        "SELECT id, name, email, password, role, status FROM users WHERE email = ? AND role = 'admin' LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        logLoginAttempt($mysqli, $email, 0);
        return ['status' => 'error', 'message' => 'Invalid admin credentials.'];
    }

    if ($user['status'] !== 'active') {
        logLoginAttempt($mysqli, $email, 0);
        return ['status' => 'error', 'message' => 'Account is suspended. Contact super admin.'];
    }

    if (!password_verify($password, $user['password'])) {
        logLoginAttempt($mysqli, $email, 0);
        return ['status' => 'error', 'message' => 'Invalid admin credentials.'];
    }

    logLoginAttempt($mysqli, $email, 1);

    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_name'] = $user['name'];
    $_SESSION['admin_email'] = $user['email'];
    $_SESSION['admin_role'] = $user['role'];
    $_SESSION['admin_last_activity'] = time();

    $updateStmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->bind_param('i', $user['id']);
    $updateStmt->execute();
    $updateStmt->close();

    return ['status' => 'success', 'message' => 'Login successful.'];
}

// =======================
// CREATE INITIAL ADMIN
// =======================
function createInitialAdmin($mysqli) {
    $check = $mysqli->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($check->num_rows == 0) {
        $name = 'Super Admin';
        $email = 'admin@shikhbo.com';
        $password = password_hash('Admin@123#Secure', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $mysqli->prepare(
            "INSERT INTO users (name, email, password, role, status, referral_code) 
             VALUES (?, ?, ?, 'admin', 'active', ?)"
        );
        $referral = 'ADMIN' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $stmt->bind_param('ssss', $name, $email, $password, $referral);
        $stmt->execute();
        $stmt->close();
        return ['email' => $email, 'password' => 'Admin@123#Secure'];
    }
    return null;
}

// =======================
// GET CURRENT ADMIN
// =======================
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) return null;
    return [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email'],
        'role' => $_SESSION['admin_role']
    ];
}

// =======================
// CHECK ROLE PERMISSION
// =======================
function hasPermission($requiredRole = 'admin') {
    return isAdminLoggedIn() && $_SESSION['admin_role'] === $requiredRole;
}