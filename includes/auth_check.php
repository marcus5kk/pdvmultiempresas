<?php
/**
 * Authentication Check
 * Include this file at the top of any page that requires authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Store the requested URL to redirect after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    $login_url = 'login.php';
    
    // Adjust path if we're in a subdirectory
    $current_dir = dirname($_SERVER['SCRIPT_NAME']);
    if (basename($current_dir) === 'pages') {
        $login_url = 'login.php';
    } else {
        $login_url = 'pages/login.php';
    }
    
    header('Location: ' . $login_url);
    exit();
}

// Optional: Check if session is still valid (not expired)
$session_timeout = 3600; // 1 hour in seconds
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $session_timeout) {
        // Session expired
        session_destroy();
        
        $login_url = 'login.php';
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        if (basename($current_dir) === 'pages') {
            $login_url = 'login.php';
        } else {
            $login_url = 'pages/login.php';
        }
        
        header('Location: ' . $login_url . '?expired=1');
        exit();
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Function to check if user has specific role
function hasRole($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $user_role = $_SESSION['role'];
    
    // System admin has access to everything
    if ($user_role === 'system_admin' || $user_role === 'admin') {
        return true;
    }
    
    // Check specific role
    return $user_role === $required_role;
}

// Function to check if user is system admin
function isSystemAdmin() {
    return hasRole('system_admin') || hasRole('admin');
}

// Function to check if user is company admin (for their company)
function isCompanyAdmin() {
    $user_role = $_SESSION['role'] ?? '';
    return $user_role === 'company_admin' || isSystemAdmin();
}

// Function to check if user can access company data
function canAccessCompany($company_id = null) {
    $user_role = $_SESSION['role'] ?? '';
    $user_company_id = $_SESSION['company_id'] ?? null;
    
    // System admin can access any company
    if (isSystemAdmin()) {
        return true;
    }
    
    // If no specific company requested, check if user has a company
    if ($company_id === null) {
        return $user_company_id !== null;
    }
    
    // Check if user belongs to the requested company
    return $user_company_id == $company_id;
}

// Function to get user company ID
function getUserCompanyId() {
    return $_SESSION['company_id'] ?? null;
}

// Function to check if user can access PDV
function canAccessPDV() {
    $user_role = $_SESSION['role'] ?? '';
    return in_array($user_role, ['system_admin', 'admin', 'company_admin', 'company_operator']);
}

// Function to check if user can access admin features
function canAccessAdminFeatures() {
    $user_role = $_SESSION['role'] ?? '';
    return in_array($user_role, ['system_admin', 'admin', 'company_admin']);
}

// Function to check if user can access reports
function canAccessReports() {
    return canAccessAdminFeatures();
}

// Function to check if user can manage products
function canManageProducts() {
    return canAccessAdminFeatures();
}

// Function to require specific role (redirect if not authorized)
function requireRole($required_role, $redirect_url = null) {
    if (!hasRole($required_role)) {
        if ($redirect_url) {
            header('Location: ' . $redirect_url);
        } else {
            // Default redirect to dashboard with error
            $dashboard_url = 'dashboard.php';
            $current_dir = dirname($_SERVER['SCRIPT_NAME']);
            if (basename($current_dir) !== 'pages') {
                $dashboard_url = 'pages/dashboard.php';
            }
            
            $_SESSION['error_message'] = 'Acesso negado. Você não tem permissão para acessar esta página.';
            header('Location: ' . $dashboard_url);
        }
        exit();
    }
}

// Function to get current user info
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
}

// Function to check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Function to sanitize output
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Prevent caching of sensitive pages
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
?>
