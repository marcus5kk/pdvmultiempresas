<?php
// Desabilita erros visíveis para API - configuração robusta
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(0);

// Buffer de saída para evitar saída de erros
ob_start();

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendResponse($success, $message, $data = null) {
    // Limpar qualquer saída anterior (erros PHP, etc)
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Garantir header JSON
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                sendResponse(false, 'Usuário e senha são obrigatórios');
            }
            
            $stmt = $db->prepare("SELECT id, username, password, full_name, email, role, company_id, active FROM users WHERE username = ? AND active = true");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($password, $user['password'])) {
                sendResponse(false, 'Usuário ou senha incorretos');
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            
            sendResponse(true, 'Login realizado com sucesso', [
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'company_id' => $user['company_id']
                ]
            ]);
            break;
            
        case 'logout':
            session_destroy();
            sendResponse(true, 'Logout realizado com sucesso');
            break;
            
        case 'check_session':
            if (!isset($_SESSION['user_id'])) {
                sendResponse(false, 'Sessão expirada');
            }
            
            sendResponse(true, 'Sessão válida', [
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'],
                    'role' => $_SESSION['role'],
                    'company_id' => $_SESSION['company_id'] ?? null
                ]
            ]);
            break;
            
        default:
            sendResponse(false, 'Ação não encontrada');
    }
    
} catch (Exception $e) {
    error_log("Auth API Error: " . $e->getMessage());
    sendResponse(false, 'Erro interno do servidor');
}
?>
