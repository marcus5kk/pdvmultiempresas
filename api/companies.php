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
    sendResponse(false, 'Acesso negado. Apenas administradores do sistema podem gerenciar empresas.');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $stmt = $db->prepare("SELECT * FROM companies ORDER BY name");
            $stmt->execute();
            $companies = $stmt->fetchAll();
            
            sendResponse(true, 'Empresas carregadas com sucesso', $companies);
            break;
            
        case 'create':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $name = trim($_POST['name'] ?? '');
            $document = trim($_POST['document'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zipcode = trim($_POST['zipcode'] ?? '');
            
            if (empty($name)) {
                sendResponse(false, 'Nome da empresa é obrigatório');
            }
            
            // Verificar se já existe empresa com o mesmo nome
            $stmt = $db->prepare("SELECT id FROM companies WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Já existe uma empresa com este nome');
            }
            
            // Verificar se documento já existe (se informado)
            if (!empty($document)) {
                $stmt = $db->prepare("SELECT id FROM companies WHERE document = ?");
                $stmt->execute([$document]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'Já existe uma empresa com este documento');
                }
            }
            
            $stmt = $db->prepare("
                INSERT INTO companies (name, document, email, phone, address, city, state, zipcode, active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $name,
                $document ?: null,
                $email ?: null,
                $phone ?: null,
                $address ?: null,
                $city ?: null,
                $state ?: null,
                $zipcode ?: null
            ]);
            
            sendResponse(true, 'Empresa criada com sucesso');
            break;
            
        case 'toggle_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $active = filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN);
            
            if ($id <= 0) {
                sendResponse(false, 'ID da empresa inválido');
            }
            
            // Verificar se a empresa existe
            $stmt = $db->prepare("SELECT id FROM companies WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                sendResponse(false, 'Empresa não encontrada');
            }
            
            $stmt = $db->prepare("UPDATE companies SET active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$active, $id]);
            
            $action_text = $active ? 'ativada' : 'desativada';
            sendResponse(true, "Empresa {$action_text} com sucesso");
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                sendResponse(false, 'Método não permitido');
            }
            
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $document = trim($_POST['document'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $zipcode = trim($_POST['zipcode'] ?? '');
            
            if ($id <= 0) {
                sendResponse(false, 'ID da empresa inválido');
            }
            
            if (empty($name)) {
                sendResponse(false, 'Nome da empresa é obrigatório');
            }
            
            // Verificar se a empresa existe
            $stmt = $db->prepare("SELECT id FROM companies WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                sendResponse(false, 'Empresa não encontrada');
            }
            
            // Verificar se já existe empresa com o mesmo nome (exceto a atual)
            $stmt = $db->prepare("SELECT id FROM companies WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Já existe uma empresa com este nome');
            }
            
            // Verificar se documento já existe (se informado e exceto a atual)
            if (!empty($document)) {
                $stmt = $db->prepare("SELECT id FROM companies WHERE document = ? AND id != ?");
                $stmt->execute([$document, $id]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'Já existe uma empresa com este documento');
                }
            }
            
            $stmt = $db->prepare("
                UPDATE companies 
                SET name = ?, document = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zipcode = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $name,
                $document ?: null,
                $email ?: null,
                $phone ?: null,
                $address ?: null,
                $city ?: null,
                $state ?: null,
                $zipcode ?: null,
                $id
            ]);
            
            sendResponse(true, 'Empresa atualizada com sucesso');
            break;
            
        case 'get':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                sendResponse(false, 'ID da empresa inválido');
            }
            
            $stmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
            $stmt->execute([$id]);
            $company = $stmt->fetch();
            
            if (!$company) {
                sendResponse(false, 'Empresa não encontrada');
            }
            
            sendResponse(true, 'Empresa encontrada', $company);
            break;
            
        default:
            sendResponse(false, 'Ação não reconhecida');
    }
    
} catch(Exception $exception) {
    error_log("Companies API Error: " . $exception->getMessage());
    sendResponse(false, 'Erro interno do servidor');
}
?>