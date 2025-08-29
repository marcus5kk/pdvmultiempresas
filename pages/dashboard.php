<?php 
require_once '../includes/auth_check.php'; 

// Verificar se o usuário admin está tentando acessar o Dashboard
if ($_SESSION['role'] === 'admin') {
    header('Location: users.php?error=access_denied');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema PDV</title>
    
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pdv.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.location.href='pdv.php'">
                                <i class="fas fa-plus"></i> Nova Venda
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card sales">
                            <div class="icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h3 id="today-sales-count">0</h3>
                            <p>Vendas Hoje</p>
                            <small class="text-muted" id="today-sales-amount">R$ 0,00</small>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card products">
                            <div class="icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <h3 id="total-products">0</h3>
                            <p>Total de Produtos</p>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card warning">
                            <div class="icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h3 id="low-stock">0</h3>
                            <p>Estoque Baixo</p>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stat-card info">
                            <div class="icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3 id="month-sales-count">0</h3>
                            <p>Vendas do Mês</p>
                            <small class="text-muted" id="month-sales-amount">R$ 0,00</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales and Quick Actions -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-clock me-2"></i>Vendas Recentes</h5>
                            </div>
                            <div class="card-body">
                                <div id="recent-sales">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i>Ações Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="pdv.php" class="btn btn-primary">
                                        <i class="fas fa-cash-register me-2"></i>
                                        Abrir PDV
                                    </a>
                                    <a href="products.php" class="btn btn-success">
                                        <i class="fas fa-plus me-2"></i>
                                        Novo Produto
                                    </a>
                                    <a href="reports.php" class="btn btn-info">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Ver Relatórios
                                    </a>
                                    <button class="btn btn-warning" onclick="checkLowStock()">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Verificar Estoque
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
    function checkLowStock() {
        window.location.href = 'reports.php?type=low_stock';
    }
    </script>
</body>
</html>
