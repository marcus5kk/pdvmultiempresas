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
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
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

function recordStockMovement($db, $product_id, $type, $quantity, $reference_type = null, $reference_id = null, $notes = null) {
    try {
        $stmt = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $type, $quantity, $reference_type, $reference_id, $notes, $_SESSION['user_id']]);
    } catch (Exception $e) {
        // Log error but continue
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
            if ($action === 'categories') {
                try {
                    $stmt = $db->prepare("SELECT * FROM categories WHERE active = true ORDER BY name");
                    $stmt->execute();
                    $categories = $stmt->fetchAll();
                    sendResponse(true, 'Categorias encontradas', $categories);
                } catch (Exception $e) {
                    sendResponse(false, 'Erro ao carregar categorias: ' . $e->getMessage());
                }
            } elseif ($action === 'search') {
                $term = $_GET['term'] ?? '';
                $barcode = $_GET['barcode'] ?? '';
                
                try {
                    if (!empty($barcode)) {
                        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.barcode = ? AND p.active = true");
                        $stmt->execute([$barcode]);
                    } else {
                        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE (LOWER(p.name) LIKE LOWER(?) OR LOWER(p.barcode) LIKE LOWER(?)) AND p.active = true ORDER BY p.name LIMIT 20");
                        $searchTerm = "%$term%";
                        $stmt->execute([$searchTerm, $searchTerm]);
                    }
                    
                    $products = $stmt->fetchAll();
                    sendResponse(true, 'Produtos encontrados', $products);
                } catch (Exception $e) {
                    sendResponse(false, 'Erro na busca: ' . $e->getMessage());
                }
            } else {
                // List all products
                try {
                    $page = intval($_GET['page'] ?? 1);
                    $limit = intval($_GET['limit'] ?? 50);
                    $offset = ($page - 1) * $limit;
                    
                    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.active = true ORDER BY p.name LIMIT ? OFFSET ?");
                    $stmt->execute([$limit, $offset]);
                    $products = $stmt->fetchAll();
                    
                    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE active = true");
                    $countStmt->execute();
                    $total = $countStmt->fetch()['total'];
                    
                    sendResponse(true, 'Produtos encontrados', [
                        'products' => $products,
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit
                    ]);
                } catch (Exception $e) {
                    sendResponse(false, 'Erro ao carregar produtos: ' . $e->getMessage());
                }
            }
            break;
            
        case 'POST':
            try {
                // Pegar dados tanto de JSON quanto de POST para compatibilidade
                $input = [];
                
                // Tentar JSON primeiro
                $json_input = json_decode(file_get_contents('php://input'), true);
                if ($json_input) {
                    $input = $json_input;
                } else {
                    $input = $_POST;
                }
                
                $name = trim($input['name'] ?? '');
                $barcode = trim($input['barcode'] ?? '');
                $category_id = intval($input['category_id'] ?? 0);
                $price = floatval($input['price'] ?? 0);
                $cost_price = floatval($input['cost_price'] ?? 0);
                $stock_quantity = intval($input['stock_quantity'] ?? 0);
                $min_stock = intval($input['min_stock'] ?? 0);
                $description = trim($input['description'] ?? '');
                
                if (empty($name) || $price <= 0) {
                    sendResponse(false, 'Nome e preço são obrigatórios');
                }
                
                // Check if barcode exists (if provided)
                if (!empty($barcode)) {
                    $stmt = $db->prepare("SELECT id FROM products WHERE barcode = ? AND active = true");
                    $stmt->execute([$barcode]);
                    if ($stmt->fetch()) {
                        sendResponse(false, 'Código de barras já existe');
                    }
                }
                
                // Insert product - sem user_id pois não existe na tabela
                $stmt = $db->prepare("INSERT INTO products (name, barcode, category_id, price, cost_price, stock_quantity, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $barcode, $category_id ?: null, $price, $cost_price, $stock_quantity, $min_stock, $description]);
                
                // Compatível com MySQL e PostgreSQL
                $product_id = $db->lastInsertId();
                if (!$product_id) {
                    // Fallback para PostgreSQL 
                    $stmt2 = $db->prepare("SELECT LASTVAL() as id");
                    $stmt2->execute();
                    $result = $stmt2->fetch();
                    $product_id = $result['id'] ?? null;
                }
                
                if (!$product_id) {
                    throw new Exception('Erro ao criar produto');
                }
                
                // Record initial stock movement
                if ($stock_quantity > 0) {
                    recordStockMovement($db, $product_id, 'in', $stock_quantity, 'initial', null, 'Estoque inicial');
                }
                
                sendResponse(true, 'Produto adicionado com sucesso', ['id' => $product_id]);
                
            } catch (Exception $e) {
                sendResponse(false, 'Erro ao adicionar produto: ' . $e->getMessage());
            }
            break;
            
        case 'PUT':
            try {
                $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $product_id = intval($_GET['id'] ?? $input['id'] ?? 0);
                
                if (!$product_id) {
                    sendResponse(false, 'ID do produto é obrigatório');
                }
            
                $name = trim($input['name'] ?? '');
                $barcode = trim($input['barcode'] ?? '');
                $price = floatval($input['price'] ?? 0);
                $cost_price = floatval($input['cost_price'] ?? 0);
                $new_stock = intval($input['stock_quantity'] ?? 0);
                $min_stock = intval($input['min_stock'] ?? 0);
                $category_id = intval($input['category_id'] ?? 0) ?: null;
                $description = trim($input['description'] ?? '');
                $unit = trim($input['unit'] ?? 'un');
                
                if (empty($name) || $price <= 0) {
                    sendResponse(false, 'Nome e preço são obrigatórios');
                }
                
                // Get current stock
                $stmt = $db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $current_product = $stmt->fetch();
                
                if (!$current_product) {
                    sendResponse(false, 'Produto não encontrado');
                }
                
                $current_stock = $current_product['stock_quantity'];
                
                // Check if barcode exists for other products
                if (!empty($barcode)) {
                    $stmt = $db->prepare("SELECT id FROM products WHERE barcode = ? AND id != ? AND active = true");
                    $stmt->execute([$barcode, $product_id]);
                    if ($stmt->fetch()) {
                        sendResponse(false, 'Código de barras já existe em outro produto');
                    }
                }
                
                $stmt = $db->prepare("UPDATE products SET name = ?, barcode = ?, description = ?, category_id = ?, price = ?, cost_price = ?, stock_quantity = ?, min_stock = ?, unit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$name, $barcode, $description, $category_id, $price, $cost_price, $new_stock, $min_stock, $unit, $product_id]);
                
                // Record stock adjustment if changed
                if ($new_stock != $current_stock) {
                    $movement_quantity = $new_stock - $current_stock;
                    $movement_type = $movement_quantity > 0 ? 'in' : 'out';
                    recordStockMovement($db, $product_id, $movement_type, abs($movement_quantity), 'adjustment', null, 'Ajuste de estoque');
                }
                
                sendResponse(true, 'Produto atualizado com sucesso');
                
            } catch (Exception $e) {
                sendResponse(false, 'Erro ao atualizar produto: ' . $e->getMessage());
            }
            break;
            
        case 'DELETE':
            try {
                $product_id = intval($_GET['id'] ?? 0);
                
                if (!$product_id) {
                    sendResponse(false, 'ID do produto é obrigatório');
                }
                
                // Soft delete
                $stmt = $db->prepare("UPDATE products SET active = false, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND active = true");
                $stmt->execute([$product_id]);
                
                if ($stmt->rowCount() === 0) {
                    sendResponse(false, 'Produto não encontrado');
                }
                
                sendResponse(true, 'Produto removido com sucesso');
                
            } catch (Exception $e) {
                sendResponse(false, 'Erro ao remover produto: ' . $e->getMessage());
            }
            break;
            
        default:
            sendResponse(false, 'Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    sendResponse(false, 'Erro interno do servidor: ' . $e->getMessage());
}
?>
