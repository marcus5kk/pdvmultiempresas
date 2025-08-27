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

// Verificação de sessão para API - retorna JSON em vez de redirect
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Sessão expirada. Faça login novamente.',
        'data' => null
    ]);
    exit;
}

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

function recordStockMovement($db, $product_id, $quantity, $sale_id) {
    try {
        $stmt = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id) VALUES (?, 'out', ?, 'sale', ?, ?)");
        $stmt->execute([$product_id, $quantity, $sale_id, $_SESSION['user_id']]);
    } catch (Exception $e) {
        error_log("Stock movement error: " . $e->getMessage());
    }
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($action === 'recent') {
                $limit = intval($_GET['limit'] ?? 10);
                $stmt = $db->prepare("SELECT s.*, u.full_name as user_name FROM sales s JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT ?");
                $stmt->execute([$limit]);
                $sales = $stmt->fetchAll();
                
                foreach ($sales as &$sale) {
                    $stmt = $db->prepare("SELECT si.*, p.name as product_name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
                    $stmt->execute([$sale['id']]);
                    $sale['items'] = $stmt->fetchAll();
                }
                
                sendResponse(true, 'Vendas encontradas', $sales);
            } else {
                sendResponse(false, 'Ação não encontrada');
            }
            break;
            
        case 'POST':
            // Pegar dados tanto de JSON quanto de POST para compatibilidade
            $input = [];
            
            // Tentar JSON primeiro
            $json_input = json_decode(file_get_contents('php://input'), true);
            if ($json_input) {
                $input = $json_input;
            } else {
                // Se JSON falhar, usar POST
                $input = $_POST;
                // Decodificar items se vier como string
                if (isset($input['items']) && is_string($input['items'])) {
                    $input['items'] = json_decode($input['items'], true);
                }
            }
            
            $items = $input['items'] ?? [];
            $payment_method = trim($input['payment_method'] ?? '');
            $payment_amount = floatval($input['payment_amount'] ?? 0);
            $discount = floatval($input['discount'] ?? 0);
            $tax = floatval($input['tax'] ?? 0);
            $notes = trim($input['notes'] ?? '');
            
            if (empty($items)) {
                sendResponse(false, 'Nenhum item foi adicionado à venda');
            }
            
            if (empty($payment_method) || $payment_amount <= 0) {
                sendResponse(false, 'Método de pagamento e valor são obrigatórios');
            }
            
            $db->beginTransaction();
            
            try {
                $total_amount = 0;
                $validated_items = [];
                
                // Validate items and calculate total
                foreach ($items as $item) {
                    $product_id = intval($item['product_id'] ?? 0);
                    $quantity = intval($item['quantity'] ?? 0);
                    $unit_price = floatval($item['unit_price'] ?? 0);
                    
                    if (!$product_id || $quantity <= 0 || $unit_price <= 0) {
                        throw new Exception('Dados do item inválidos');
                    }
                    
                    // Check product and stock
                    $stmt = $db->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND active = true");
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch();
                    
                    if (!$product) {
                        throw new Exception('Produto não encontrado: ID ' . $product_id);
                    }
                    
                    if ($product['stock_quantity'] < $quantity) {
                        throw new Exception('Estoque insuficiente para ' . $product['name']);
                    }
                    
                    $item_total = $quantity * $unit_price;
                    $total_amount += $item_total;
                    
                    $validated_items[] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total_price' => $item_total
                    ];
                }
                
                // Apply discount and tax
                $total_amount = $total_amount - $discount + $tax;
                $change_amount = $payment_amount - $total_amount;
                
                if ($change_amount < 0) {
                    throw new Exception('Valor do pagamento insuficiente');
                }
                
                // Create sale - compatível com PostgreSQL
                $database_type = DB_TYPE;
                
                if ($database_type === 'postgresql') {
                    // PostgreSQL com RETURNING
                    $stmt = $db->prepare("INSERT INTO sales (user_id, total_amount, discount, tax, payment_method, payment_amount, change_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id");
                    $stmt->execute([$_SESSION['user_id'], $total_amount, $discount, $tax, $payment_method, $payment_amount, $change_amount, $notes]);
                    $result = $stmt->fetch();
                    $sale_id = $result['id'] ?? null;
                } else {
                    // MySQL com lastInsertId
                    $stmt = $db->prepare("INSERT INTO sales (user_id, total_amount, discount, tax, payment_method, payment_amount, change_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $total_amount, $discount, $tax, $payment_method, $payment_amount, $change_amount, $notes]);
                    $sale_id = $db->lastInsertId();
                }
                
                if (!$sale_id) {
                    throw new Exception('Erro ao criar venda - ID não obtido');
                }
                
                // Create sale items and update stock
                foreach ($validated_items as $item) {
                    // Insert sale item
                    $stmt = $db->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$sale_id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total_price']]);
                    
                    // Update product stock
                    $stmt = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                    
                    // Record stock movement
                    recordStockMovement($db, $item['product_id'], $item['quantity'], $sale_id);
                }
                
                $db->commit();
                
                sendResponse(true, 'Venda realizada com sucesso', [
                    'sale_id' => $sale_id,
                    'total_amount' => $total_amount,
                    'change_amount' => $change_amount
                ]);
                
            } catch (Exception $e) {
                $db->rollback();
                throw $e;
            }
            break;
            
        default:
            sendResponse(false, 'Método não permitido');
    }
    
} catch (Exception $e) {
    // Limpar buffer de erros
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Sales API Error: " . $e->getMessage());
    sendResponse(false, 'Erro ao processar venda: ' . $e->getMessage());
} catch (Error $e) {
    // Capturar erros fatais também
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Sales API Fatal Error: " . $e->getMessage());
    sendResponse(false, 'Erro interno do servidor');
}
?>
