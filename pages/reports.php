<?php require_once '../includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Sistema PDV</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
                            <a class="nav-link active" href="reports.php">
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
                    <h1 class="h2">Relatórios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportReport()">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printReport()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Report Type Selection -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="report-type" class="form-label">Tipo de Relatório</label>
                                        <select class="form-select" id="report-type">
                                            <option value="sales">Vendas</option>
                                            <option value="products">Produtos</option>
                                            <option value="low_stock">Estoque Baixo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start-date" class="form-label">Data Inicial</label>
                                        <input type="date" class="form-control" id="start-date">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="end-date" class="form-label">Data Final</label>
                                        <input type="date" class="form-control" id="end-date">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid">
                                            <button type="button" class="btn btn-primary" onclick="generateReport()">
                                                <i class="fas fa-chart-bar me-2"></i>Gerar Relatório
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div id="report-content">
                    <!-- Sales Report -->
                    <div id="sales-report" class="report-section" style="display: none;">
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-chart-line me-2"></i>Relatório de Vendas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-primary" id="total-sales-count">0</h4>
                                                    <small class="text-muted">Total de Vendas</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-success" id="total-sales-amount">R$ 0,00</h4>
                                                    <small class="text-muted">Valor Total</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-info" id="average-sale">R$ 0,00</h4>
                                                    <small class="text-muted">Ticket Médio</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="text-center">
                                                    <h4 class="text-warning" id="total-discount">R$ 0,00</h4>
                                                    <small class="text-muted">Total Descontos</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Sales Chart -->
                                        <div class="mt-4">
                                            <canvas id="sales-chart" width="400" height="100"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6>Detalhes por Data</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Data</th>
                                                        <th>Qtd. Vendas</th>
                                                        <th>Valor Total</th>
                                                        <th>Desconto</th>
                                                        <th>Impostos</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="sales-details-tbody">
                                                    <!-- Sales details will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Report -->
                    <div id="products-report" class="report-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-boxes me-2"></i>Relatório de Produtos</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nome</th>
                                                        <th>Código de Barras</th>
                                                        <th>Categoria</th>
                                                        <th>Preço</th>
                                                        <th>Estoque</th>
                                                        <th>Vendidos</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="products-report-tbody">
                                                    <!-- Products report will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Report -->
                    <div id="low-stock-report" class="report-section" style="display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Produtos com Estoque Baixo</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Os produtos listados abaixo estão com estoque igual ou inferior ao estoque mínimo configurado.
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Nome</th>
                                                        <th>Código de Barras</th>
                                                        <th>Categoria</th>
                                                        <th>Estoque Atual</th>
                                                        <th>Estoque Mínimo</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="low-stock-tbody">
                                                    <!-- Low stock products will be loaded here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Default message -->
                    <div id="default-message" class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Selecione um tipo de relatório e clique em "Gerar Relatório"</h5>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
    let salesChart = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Set default dates
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        document.getElementById('start-date').value = firstDay.toISOString().split('T')[0];
        document.getElementById('end-date').value = today.toISOString().split('T')[0];

        // Check for URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const reportType = urlParams.get('type');
        
        if (reportType) {
            document.getElementById('report-type').value = reportType;
            generateReport();
        }
    });

    async function generateReport() {
        const reportType = document.getElementById('report-type').value;
        const startDate = document.getElementById('start-date').value;
        const endDate = document.getElementById('end-date').value;

        // Hide all report sections
        document.querySelectorAll('.report-section').forEach(section => {
            section.style.display = 'none';
        });
        document.getElementById('default-message').style.display = 'none';

        try {
            switch (reportType) {
                case 'sales':
                    await generateSalesReport(startDate, endDate);
                    break;
                case 'products':
                    await generateProductsReport();
                    break;
                case 'low_stock':
                    await generateLowStockReport();
                    break;
            }
        } catch (error) {
            console.error('Error generating report:', error);
            pdvSystem.showAlert('danger', 'Erro ao gerar relatório');
        }
    }

    async function generateSalesReport(startDate, endDate) {
        try {
            const response = await fetch(`../api/reports.php?action=sales&start_date=${startDate}&end_date=${endDate}`);
            const data = await response.json();

            if (data.success) {
                displaySalesReport(data.data);
                document.getElementById('sales-report').style.display = 'block';
            } else {
                pdvSystem.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error loading sales report:', error);
            pdvSystem.showAlert('danger', 'Erro ao carregar relatório de vendas');
        }
    }

    function displaySalesReport(data) {
        // Update summary
        document.getElementById('total-sales-count').textContent = data.totals.total_sales;
        document.getElementById('total-sales-amount').textContent = pdvSystem.formatCurrency(data.totals.total_amount);
        document.getElementById('total-discount').textContent = pdvSystem.formatCurrency(data.totals.total_discount);
        
        const averageSale = data.totals.total_sales > 0 ? data.totals.total_amount / data.totals.total_sales : 0;
        document.getElementById('average-sale').textContent = pdvSystem.formatCurrency(averageSale);

        // Update details table
        const tbody = document.getElementById('sales-details-tbody');
        if (data.sales.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">Nenhuma venda encontrada no período</td></tr>';
        } else {
            tbody.innerHTML = data.sales.map(sale => `
                <tr>
                    <td>${pdvSystem.formatDate(sale.sale_date)}</td>
                    <td>${sale.sales_count}</td>
                    <td>${pdvSystem.formatCurrency(sale.total_amount)}</td>
                    <td>${pdvSystem.formatCurrency(sale.total_discount)}</td>
                    <td>${pdvSystem.formatCurrency(sale.total_tax)}</td>
                </tr>
            `).join('');
        }

        // Update chart
        updateSalesChart(data.sales);
    }

    function updateSalesChart(salesData) {
        const ctx = document.getElementById('sales-chart').getContext('2d');
        
        if (salesChart) {
            salesChart.destroy();
        }

        const labels = salesData.map(sale => pdvSystem.formatDate(sale.sale_date));
        const amounts = salesData.map(sale => parseFloat(sale.total_amount) || 0);

        salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Vendas (R$)',
                    data: amounts,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Vendas por Data'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });
    }

    async function generateProductsReport() {
        try {
            const response = await fetch('../api/reports.php?action=products');
            const data = await response.json();

            if (data.success) {
                displayProductsReport(data.data);
                document.getElementById('products-report').style.display = 'block';
            } else {
                pdvSystem.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error loading products report:', error);
            pdvSystem.showAlert('danger', 'Erro ao carregar relatório de produtos');
        }
    }

    function displayProductsReport(products) {
        const tbody = document.getElementById('products-report-tbody');
        
        if (products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum produto encontrado</td></tr>';
        } else {
            tbody.innerHTML = products.map(product => `
                <tr>
                    <td>${product.id}</td>
                    <td>${product.name}</td>
                    <td>${product.barcode || '-'}</td>
                    <td>${product.category_name || '-'}</td>
                    <td>${pdvSystem.formatCurrency(product.price)}</td>
                    <td>
                        <span class="badge ${product.stock_quantity <= product.min_stock ? 'bg-warning' : 'bg-success'}">
                            ${product.stock_quantity}
                        </span>
                    </td>
                    <td>${product.total_sold}</td>
                </tr>
            `).join('');
        }
    }

    async function generateLowStockReport() {
        try {
            const response = await fetch('../api/reports.php?action=low_stock');
            const data = await response.json();

            if (data.success) {
                displayLowStockReport(data.data);
                document.getElementById('low-stock-report').style.display = 'block';
            } else {
                pdvSystem.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error loading low stock report:', error);
            pdvSystem.showAlert('danger', 'Erro ao carregar relatório de estoque baixo');
        }
    }

    function displayLowStockReport(products) {
        const tbody = document.getElementById('low-stock-tbody');
        
        if (products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-success">Nenhum produto com estoque baixo</td></tr>';
        } else {
            tbody.innerHTML = products.map(product => `
                <tr>
                    <td>${product.id}</td>
                    <td>${product.name}</td>
                    <td>${product.barcode || '-'}</td>
                    <td>${product.category_name || '-'}</td>
                    <td>
                        <span class="badge ${product.stock_quantity === 0 ? 'bg-danger' : 'bg-warning'}">
                            ${product.stock_quantity}
                        </span>
                    </td>
                    <td>${product.min_stock}</td>
                    <td>
                        ${product.stock_quantity === 0 ? 
                            '<span class="badge bg-danger">Sem Estoque</span>' : 
                            '<span class="badge bg-warning">Estoque Baixo</span>'
                        }
                    </td>
                </tr>
            `).join('');
        }
    }

    function exportReport() {
        // Implementation for exporting report to CSV/PDF
        pdvSystem.showAlert('info', 'Funcionalidade de exportação em desenvolvimento');
    }

    function printReport() {
        window.print();
    }
    </script>
</body>
</html>
