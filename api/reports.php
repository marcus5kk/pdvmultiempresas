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
require_once '../includes/auth_check.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            // Sales today
            $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE DATE(created_at) = CURRENT_DATE");
            $stmt->execute();
            $today_sales = $stmt->fetch();
            
            // Sales this month
            $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total FROM sales WHERE YEAR(created_at) = YEAR(CURRENT_DATE) AND MONTH(created_at) = MONTH(CURRENT_DATE)");
            $stmt->execute();
            $month_sales = $stmt->fetch();
            
            // Low stock products
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= min_stock AND active = true");
            $stmt->execute();
            $low_stock = $stmt->fetch();
            
            // Total products
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE active = true");
            $stmt->execute();
            $total_products = $stmt->fetch();
            
            sendResponse(true, 'Dashboard data', [
                'today_sales' => $today_sales,
                'month_sales' => $month_sales,
                'low_stock' => $low_stock['count'],
                'total_products' => $total_products['count']
            ]);
            break;
            
        case 'sales':
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            
            $stmt = $db->prepare("
                SELECT 
                    DATE(s.created_at) as sale_date,
                    COUNT(s.id) as sales_count,
                    SUM(s.total_amount) as total_amount,
                    SUM(s.discount) as total_discount,
                    SUM(s.tax) as total_tax
                FROM sales s 
                WHERE DATE(s.created_at) BETWEEN ? AND ?
                GROUP BY DATE(s.created_at)
                ORDER BY sale_date DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $sales_report = $stmt->fetchAll();
            
            // Get totals
            $stmt = $db->prepare("
                SELECT 
                    COUNT(s.id) as total_sales,
                    COALESCE(SUM(s.total_amount), 0) as total_amount,
                    COALESCE(SUM(s.discount), 0) as total_discount,
                    COALESCE(SUM(s.tax), 0) as total_tax
                FROM sales s 
                WHERE DATE(s.created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$start_date, $end_date]);
            $totals = $stmt->fetch();
            
            sendResponse(true, 'Relatório de vendas', [
                'sales' => $sales_report,
                'totals' => $totals,
                'period' => ['start' => $start_date, 'end' => $end_date]
            ]);
            break;
            
        case 'products':
            $stmt = $db->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.barcode,
                    p.price,
                    p.stock_quantity,
                    p.min_stock,
                    c.name as category_name,
                    COALESCE(SUM(si.quantity), 0) as total_sold
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN sale_items si ON p.id = si.product_id
                WHERE p.active = true
                GROUP BY p.id, p.name, p.barcode, p.price, p.stock_quantity, p.min_stock, c.name
                ORDER BY total_sold DESC
            ");
            $stmt->execute();
            $products_report = $stmt->fetchAll();
            
            sendResponse(true, 'Relatório de produtos', $products_report);
            break;
            
        case 'low_stock':
            $stmt = $db->prepare("
                SELECT 
                    p.id,
                    p.name,
                    p.barcode,
                    p.stock_quantity,
                    p.min_stock,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.stock_quantity <= p.min_stock AND p.active = true
                ORDER BY p.stock_quantity ASC
            ");
            $stmt->execute();
            $low_stock_products = $stmt->fetchAll();
            
            sendResponse(true, 'Produtos com estoque baixo', $low_stock_products);
            break;
            
        default:
            sendResponse(false, 'Relatório não encontrado');
    }
    
} catch (Exception $e) {
    // Limpar buffer de erros
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Reports API Error: " . $e->getMessage());
    sendResponse(false, 'Erro ao gerar relatório: ' . $e->getMessage());
} catch (Error $e) {
    // Capturar erros fatais também
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Reports API Fatal Error: " . $e->getMessage());
    sendResponse(false, 'Erro interno do servidor');
}
?>
