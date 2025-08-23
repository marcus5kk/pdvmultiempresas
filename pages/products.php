<?php require_once '../includes/auth_check.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Sistema PDV</title>
    
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
                            <a class="nav-link" href="pdv.php">
                                <i class="fas fa-cash-register"></i>
                                PDV
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="products.php">
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
                    <h1 class="h2">Gerenciar Produtos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                                <i class="fas fa-plus"></i> Novo Produto
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="search-products" placeholder="Buscar produtos...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filter-category">
                            <option value="">Todas as categorias</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filter-stock">
                            <option value="">Todos os estoques</option>
                            <option value="low">Estoque baixo</option>
                            <option value="zero">Sem estoque</option>
                        </select>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
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
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="products-tbody">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Carregando...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Novo Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="product-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="product-name" class="form-label">Nome do Produto *</label>
                                    <input type="text" class="form-control" id="product-name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="product-barcode" class="form-label">Código de Barras</label>
                                    <input type="text" class="form-control" id="product-barcode" name="barcode">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-category" class="form-label">Categoria</label>
                                    <select class="form-select" id="product-category" name="category_id">
                                        <option value="">Selecione uma categoria</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-unit" class="form-label">Unidade</label>
                                    <select class="form-select" id="product-unit" name="unit">
                                        <option value="un">Unidade</option>
                                        <option value="kg">Quilograma</option>
                                        <option value="g">Grama</option>
                                        <option value="l">Litro</option>
                                        <option value="ml">Mililitro</option>
                                        <option value="m">Metro</option>
                                        <option value="cm">Centímetro</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-price" class="form-label">Preço de Venda *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="product-price" name="price" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-cost-price" class="form-label">Preço de Custo</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="product-cost-price" name="cost_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-stock" class="form-label">Estoque Inicial</label>
                                    <input type="number" class="form-control" id="product-stock" name="stock_quantity" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product-min-stock" class="form-label">Estoque Mínimo</label>
                                    <input type="number" class="form-control" id="product-min-stock" name="min_stock" min="0" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="product-description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="product-description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Produto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
    // Products management functionality
    let currentProductId = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadCategories();
        setupProductForm();
        setupSearch();
    });

    async function loadCategories() {
        try {
            const response = await fetch(apiUrl('products.php?action=categories'));
            const data = await response.json();

            if (data.success) {
                const categorySelects = document.querySelectorAll('#product-category, #filter-category');
                categorySelects.forEach(select => {
                    if (select.id === 'filter-category') {
                        select.innerHTML = '<option value="">Todas as categorias</option>';
                    } else {
                        select.innerHTML = '<option value="">Selecione uma categoria</option>';
                    }

                    data.data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    function setupProductForm() {
        const form = document.getElementById('product-form');
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const url = currentProductId ? apiUrl(`products.php?id=${currentProductId}`) : apiUrl('products.php');
                const method = currentProductId ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    pdvSystem.showAlert('success', currentProductId ? 'Produto atualizado com sucesso!' : 'Produto cadastrado com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                    pdvSystem.loadProductsData();
                    form.reset();
                    currentProductId = null;
                    document.getElementById('productModalTitle').textContent = 'Novo Produto';
                } else {
                    pdvSystem.showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error saving product:', error);
                pdvSystem.showAlert('danger', 'Erro ao salvar produto');
            }
        });
    }

    function setupSearch() {
        const searchInput = document.getElementById('search-products');
        const categoryFilter = document.getElementById('filter-category');
        const stockFilter = document.getElementById('filter-stock');

        searchInput.addEventListener('input', debounce(filterProducts, 300));
        categoryFilter.addEventListener('change', filterProducts);
        stockFilter.addEventListener('change', filterProducts);
    }

    function filterProducts() {
        // Implementation for filtering products
        pdvSystem.loadProductsData();
    }

    function editProduct(id) {
        currentProductId = id;
        // Load product data and populate form
        loadProductForEdit(id);
        document.getElementById('productModalTitle').textContent = 'Editar Produto';
        new bootstrap.Modal(document.getElementById('productModal')).show();
    }

    async function loadProductForEdit(id) {
        try {
            const response = await fetch(apiUrl(`products.php?id=${id}`));
            const data = await response.json();

            if (data.success) {
                const product = data.data;
                document.getElementById('product-name').value = product.name || '';
                document.getElementById('product-barcode').value = product.barcode || '';
                document.getElementById('product-category').value = product.category_id || '';
                document.getElementById('product-unit').value = product.unit || 'un';
                document.getElementById('product-price').value = product.price || '';
                document.getElementById('product-cost-price').value = product.cost_price || '';
                document.getElementById('product-stock').value = product.stock_quantity || 0;
                document.getElementById('product-min-stock').value = product.min_stock || 0;
                document.getElementById('product-description').value = product.description || '';
            }
        } catch (error) {
            console.error('Error loading product:', error);
            pdvSystem.showAlert('danger', 'Erro ao carregar produto');
        }
    }

    async function deleteProduct(id) {
        if (!confirm('Tem certeza que deseja excluir este produto?')) {
            return;
        }

        try {
            const response = await fetch(apiUrl(`products.php?id=${id}`), {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                pdvSystem.showAlert('success', 'Produto excluído com sucesso!');
                pdvSystem.loadProductsData();
            } else {
                pdvSystem.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error deleting product:', error);
            pdvSystem.showAlert('danger', 'Erro ao excluir produto');
        }
    }

    // Reset form when modal is hidden
    document.getElementById('productModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('product-form').reset();
        currentProductId = null;
        document.getElementById('productModalTitle').textContent = 'Novo Produto';
    });
    </script>
</body>
</html>
