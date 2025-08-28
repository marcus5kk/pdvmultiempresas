<?php
// Iniciar sessão
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendResponse($success, $message, $data = null) {
    // Limpar qualquer saída anterior
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Usuário não autenticado');
}

// Verificar se é admin do sistema
if (!hasRole('system_admin')) {
    sendResponse(false, 'Acesso negado. Apenas administradores do sistema podem gerenciar usuários.');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $stmt = $db->prepare("
                SELECT u.*, c.name as company_name 
                FROM users u 
                LEFT JOIN companies c ON u.company_id = c.id 
                ORDER BY u.full_name
            ");
            $stmt->execute();
            $users = $stmt->fetchAll();
            
            sendResponse(true, 'Usuários carregados com sucesso', $users);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $company_id = intval($_POST['company_id'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            
            if ($company_id <= 0) {
                sendResponse(false, 'Empresa é obrigatória');
            }
            
            if (empty($full_name)) {
                sendResponse(false, 'Nome completo é obrigatório');
            }
            
            if (empty($username)) {
                sendResponse(false, 'Nome de usuário é obrigatório');
            }
            
            if (empty($password)) {
                sendResponse(false, 'Senha é obrigatória');
            }
            
            if (!in_array($role, ['company_admin', 'company_operator'])) {
                sendResponse(false, 'Nível de acesso inválido');
            }
            
            // Verificar se a empresa existe e está ativa
            $stmt = $db->prepare("SELECT id FROM companies WHERE id = ? AND active = true");
            $stmt->execute([$company_id]);
            if (!$stmt->fetch()) {
                sendResponse(false, 'Empresa não encontrada ou inativa');
            }
            
            // Verificar se username já existe
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Nome de usuário já existe');
            }
            
            // Verificar se email já existe (se informado)
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'E-mail já está em uso');
                }
            }
            
            // Hash da senha
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("
                INSERT INTO users (company_id, username, password, full_name, email, role, active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $company_id,
                $username,
                $hashed_password,
                $full_name,
                $email ?: null,
                $role
            ]);
            
            sendResponse(true, 'Usuário criado com sucesso');
            break;
            
        case 'toggle_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $active = filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN);
            
            if ($id <= 0) {
                sendResponse(false, 'ID do usuário inválido');
            }
            
            // Verificar se o usuário existe e não é system_admin
            $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                sendResponse(false, 'Usuário não encontrado');
            }
            
            if ($user['role'] === 'system_admin') {
                sendResponse(false, 'Não é possível alterar o status do administrador do sistema');
            }
            
            $stmt = $db->prepare("UPDATE users SET active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$active, $id]);
            
            $action_text = $active ? 'ativado' : 'desativado';
            sendResponse(true, "Usuário {$action_text} com sucesso");
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $company_id = intval($_POST['company_id'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            
            if ($id <= 0) {
                sendResponse(false, 'ID do usuário inválido');
            }
            
            if ($company_id <= 0) {
                sendResponse(false, 'Empresa é obrigatória');
            }
            
            if (empty($full_name)) {
                sendResponse(false, 'Nome completo é obrigatório');
            }
            
            if (empty($username)) {
                sendResponse(false, 'Nome de usuário é obrigatório');
            }
            
            if (!in_array($role, ['company_admin', 'company_operator'])) {
                sendResponse(false, 'Nível de acesso inválido');
            }
            
            // Verificar se o usuário existe e não é system_admin
            $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                sendResponse(false, 'Usuário não encontrado');
            }
            
            if ($user['role'] === 'system_admin') {
                sendResponse(false, 'Não é possível editar o administrador do sistema');
            }
            
            // Verificar se a empresa existe e está ativa
            $stmt = $db->prepare("SELECT id FROM companies WHERE id = ? AND active = true");
            $stmt->execute([$company_id]);
            if (!$stmt->fetch()) {
                sendResponse(false, 'Empresa não encontrada ou inativa');
            }
            
            // Verificar se username já existe (exceto o atual)
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Nome de usuário já existe');
            }
            
            // Verificar se email já existe (se informado e exceto o atual)
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'E-mail já está em uso');
                }
            }
            
            // Preparar query de update
            $update_fields = [
                $company_id,
                $username,
                $full_name,
                $email ?: null,
                $role,
                $id
            ];
            
            $sql = "
                UPDATE users 
                SET company_id = ?, username = ?, full_name = ?, email = ?, role = ?, updated_at = CURRENT_TIMESTAMP
            ";
            
            // Se nova senha foi informada, incluir no update
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                array_splice($update_fields, -1, 0, [$hashed_password]); // Inserir antes do ID
            }
            
            $sql .= " WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($update_fields);
            
            sendResponse(true, 'Usuário atualizado com sucesso');
            break;
            
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                sendResponse(false, 'ID do usuário inválido');
            }
            
            $stmt = $db->prepare("
                SELECT u.*, c.name as company_name 
                FROM users u 
                LEFT JOIN companies c ON u.company_id = c.id 
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                sendResponse(false, 'Usuário não encontrado');
            }
            
            // Remover senha do retorno
            unset($user['password']);
            
            sendResponse(true, 'Usuário encontrado', $user);
            break;
            
        default:
            sendResponse(false, 'Ação não reconhecida');
    }
    
} catch(Exception $exception) {
    error_log("Users API Error: " . $exception->getMessage());
    sendResponse(false, 'Erro interno do servidor');
}
?>