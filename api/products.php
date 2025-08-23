<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function recordStockMovement($db, $product_id, $type, $quantity, $reference_type = null, $reference_id = null, $notes = null) {
    $stmt = $db->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$product_id, $type, $quantity, $reference_type, $reference_id, $notes, $_SESSION['user_id']]);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            if ($action === 'categories') {
                $stmt = $db->prepare("SELECT * FROM categories WHERE active = true ORDER BY name");
                $stmt->execute();
                $categories = $stmt->fetchAll();
                sendResponse(true, 'Categorias encontradas', $categories);
            } elseif ($action === 'search') {
                $term = $_GET['term'] ?? '';
                $barcode = $_GET['barcode'] ?? '';
                
                if (!empty($barcode)) {
                    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.barcode = ? AND p.active = true");
                    $stmt->execute([$barcode]);
                } else {
                    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.name ILIKE ? OR p.barcode ILIKE ?) AND p.active = true ORDER BY p.name LIMIT 20");
                    $searchTerm = "%$term%";
                    $stmt->execute([$searchTerm, $searchTerm]);
                }
                
                $products = $stmt->fetchAll();
                sendResponse(true, 'Produtos encontrados', $products);
            } else {
                // List all products
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
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $name = trim($input['name'] ?? '');
            $barcode = trim($input['barcode'] ?? '');
            $price = floatval($input['price'] ?? 0);
            $cost_price = floatval($input['cost_price'] ?? 0);
            $stock_quantity = intval($input['stock_quantity'] ?? 0);
            $min_stock = intval($input['min_stock'] ?? 0);
            $category_id = intval($input['category_id'] ?? 0) ?: null;
            $description = trim($input['description'] ?? '');
            $unit = trim($input['unit'] ?? 'un');
            
            if (empty($name) || $price <= 0) {
                sendResponse(false, 'Nome e preço são obrigatórios');
            }
            
            // Check if barcode exists
            if (!empty($barcode)) {
                $stmt = $db->prepare("SELECT id FROM products WHERE barcode = ? AND active = true");
                $stmt->execute([$barcode]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'Código de barras já existe');
                }
            }
            
            $stmt = $db->prepare("INSERT INTO products (name, barcode, description, category_id, price, cost_price, stock_quantity, min_stock, unit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id");
            $stmt->execute([$name, $barcode, $description, $category_id, $price, $cost_price, $stock_quantity, $min_stock, $unit]);
            $product_id = $stmt->fetch()['id'];
            
            // Record initial stock movement
            if ($stock_quantity > 0) {
                recordStockMovement($db, $product_id, 'in', $stock_quantity, 'initial', null, 'Estoque inicial');
            }
            
            sendResponse(true, 'Produto cadastrado com sucesso', ['id' => $product_id]);
            break;
            
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
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
            $stmt->execute([$id]);
            $current_product = $stmt->fetch();
            
            if (!$current_product) {
                sendResponse(false, 'Produto não encontrado');
            }
            
            $current_stock = $current_product['stock_quantity'];
            
            // Check if barcode exists for other products
            if (!empty($barcode)) {
                $stmt = $db->prepare("SELECT id FROM products WHERE barcode = ? AND id != ? AND active = true");
                $stmt->execute([$barcode, $id]);
                if ($stmt->fetch()) {
                    sendResponse(false, 'Código de barras já existe em outro produto');
                }
            }
            
            $stmt = $db->prepare("UPDATE products SET name = ?, barcode = ?, description = ?, category_id = ?, price = ?, cost_price = ?, stock_quantity = ?, min_stock = ?, unit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$name, $barcode, $description, $category_id, $price, $cost_price, $new_stock, $min_stock, $unit, $id]);
            
            // Record stock adjustment if changed
            if ($new_stock != $current_stock) {
                $movement_quantity = $new_stock - $current_stock;
                $movement_type = $movement_quantity > 0 ? 'in' : 'out';
                recordStockMovement($db, $id, $movement_type, abs($movement_quantity), 'adjustment', null, 'Ajuste de estoque');
            }
            
            sendResponse(true, 'Produto atualizado com sucesso');
            break;
            
        case 'DELETE':
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                sendResponse(false, 'ID do produto é obrigatório');
            }
            
            $stmt = $db->prepare("UPDATE products SET active = false WHERE id = ?");
            $stmt->execute([$id]);
            
            sendResponse(true, 'Produto removido com sucesso');
            break;
            
        default:
            sendResponse(false, 'Método não permitido');
    }
    
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    sendResponse(false, 'Erro interno do servidor: ' . $e->getMessage());
}
?>
