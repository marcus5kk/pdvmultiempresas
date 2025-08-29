<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info from session
$user_name = $_SESSION['full_name'] ?? 'Usuário';
$user_role = $_SESSION['role'] ?? 'operator';
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <?php if ($user_role === 'admin' || $user_role === 'system_admin'): ?>
        <span class="navbar-brand">
            <i class="fas fa-cash-register me-2"></i>
            Sistema PDV
        </span>
        <?php else: ?>
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-cash-register me-2"></i>
            Sistema PDV
        </a>
        <?php endif; ?>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($user_role === 'admin' || $user_role === 'system_admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-1"></i>
                        Usuários
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="companies.php">
                        <i class="fas fa-building me-1"></i>
                        Empresas
                    </a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pdv.php">
                        <i class="fas fa-cash-register me-1"></i>
                        PDV
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-1"></i>
                        Produtos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar me-1"></i>
                        Relatórios
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <span id="user-info"><?php echo htmlspecialchars($user_name); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <small class="text-muted">
                                    <?php echo ucfirst($user_role); ?>
                                </small>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($user_role === 'admin' || $user_role === 'system_admin'): ?>
                        <li>
                            <a class="dropdown-item admin-only" href="users.php">
                                <i class="fas fa-users me-2"></i>
                                Usuários
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item admin-only" href="settings.php">
                                <i class="fas fa-cog me-2"></i>
                                Configurações
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user-edit me-2"></i>
                                Meu Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" id="logout-btn">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Sair
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
