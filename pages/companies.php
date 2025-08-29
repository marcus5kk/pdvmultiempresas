<?php 
require_once '../includes/auth_check.php';

// Verificar se é admin do sistema
if (!hasRole('system_admin') && !hasRole('admin')) {
    header('Location: users.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Empresas - Sistema PDV</title>
    
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
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                Usuários
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="companies.php">
                                <i class="fas fa-building"></i>
                                Empresas
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gerenciar Empresas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addCompanyModal">
                                <i class="fas fa-plus"></i> Nova Empresa
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alert area -->
                <div id="alert-container"></div>

                <!-- Companies Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-building me-2"></i>Empresas Cadastradas</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover" id="companies-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome da Empresa</th>
                                                <th>Documento</th>
                                                <th>E-mail</th>
                                                <th>Telefone</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
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
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Company Modal -->
    <div class="modal fade" id="addCompanyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="add-company-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company-name" class="form-label">Nome da Empresa *</label>
                                    <input type="text" class="form-control" id="company-name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company-document" class="form-label">CNPJ</label>
                                    <input type="text" class="form-control" id="company-document" name="document" placeholder="00.000.000/0000-00">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company-email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="company-email" name="email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company-phone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control" id="company-phone" name="phone" placeholder="(00) 0000-0000">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="company-address" class="form-label">Endereço</label>
                            <textarea class="form-control" id="company-address" name="address" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company-city" class="form-label">Cidade</label>
                                    <input type="text" class="form-control" id="company-city" name="city">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="company-state" class="form-label">Estado</label>
                                    <input type="text" class="form-control" id="company-state" name="state" maxlength="2">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="company-zipcode" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="company-zipcode" name="zipcode" placeholder="00000-000">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Salvar Empresa</button>
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

</body>
</html>