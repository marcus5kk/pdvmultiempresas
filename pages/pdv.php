<?php 
require_once '../includes/auth_check.php'; 

// Verificar se o usuário admin está tentando acessar o PDV
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'system_admin') {
    header('Location: users.php?error=access_denied');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV - Sistema PDV</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="pdv.php">
                                <i class="fas fa-cash-register"></i>
                                PDV
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box"></i>
                                Produtos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i>
                                Relatórios
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Ponto de Venda (PDV)</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-secondary" id="clear-cart">
                                <i class="fas fa-trash"></i> Limpar Carrinho
                            </button>
                        </div>
                    </div>
                </div>

                <div id="pdv-container" class="pdv-container">
                    <div class="row">
                        <!-- Product Search and Cart -->
                        <div class="col-lg-8">
                            <!-- Search Section -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h5><i class="fas fa-search me-2"></i>Buscar Produtos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="product-search">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="fas fa-search"></i>
                                                    </span>
                                                    <input type="text" class="form-control" id="product-search" 
                                                           placeholder="Digite o nome do produto..." autocomplete="off">
                                                </div>
                                                <div id="search-results" class="search-results" style="display: none;"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-barcode"></i>
                                                </span>
                                                <input type="text" class="form-control" id="barcode-search" 
                                                       placeholder="Código de barras" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Categories -->
                                    <div class="mt-3">
                                        <label class="form-label">Categorias:</label>
                                        <div id="categories-container" class="d-flex flex-wrap gap-2">
                                            <!-- Categories will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shopping Cart -->
                            <div class="card">
                                <div class="card-header">
                                    <h5>
                                        <i class="fas fa-shopping-cart me-2"></i>
                                        Carrinho de Compras 
                                        <span class="badge bg-primary" id="cart-item-count">0</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="cart-items">
                                        <p class="text-muted text-center">Carrinho vazio</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div class="col-lg-4">
                            <div class="cart-summary">
                                <h5><i class="fas fa-calculator me-2"></i>Resumo da Venda</h5>
                                
                                <div class="total-display">
                                    <div>TOTAL</div>
                                    <div id="cart-total">R$ 0,00</div>
                                </div>

                                <form id="payment-form">
                                    <div class="mb-3">
                                        <label for="payment-method" class="form-label">Método de Pagamento</label>
                                        <select class="form-select" id="payment-method" name="payment_method" required>
                                            <option value="">Selecione o método</option>
                                            <option value="dinheiro">Dinheiro</option>
                                            <option value="cartao_debito">Cartão de Débito</option>
                                            <option value="cartao_credito">Cartão de Crédito</option>
                                            <option value="pix">PIX</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="payment-amount" class="form-label">Valor Pago</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" class="form-control" id="payment-amount" 
                                                   name="payment_amount" step="0.01" min="0" required>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="discount" class="form-label">Desconto</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" class="form-control" id="discount" 
                                                   name="discount" step="0.01" min="0" value="0">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Troco:</strong>
                                            <strong id="change-amount" class="text-success">R$ 0,00</strong>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sale-notes" class="form-label">Observações</label>
                                        <textarea class="form-control" id="sale-notes" name="notes" rows="2"></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success btn-lg" id="process-sale-btn" disabled>
                                            <i class="fas fa-credit-card me-2"></i>
                                            Finalizar Venda
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div class="modal fade" id="receipt-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comprovante de Venda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="receipt-content">
                    <!-- Receipt content will be generated here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/pdv.js"></script>
</body>
</html>
