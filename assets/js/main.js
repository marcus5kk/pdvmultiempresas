// Sistema PDV - JavaScript principal
class PDVSystem {
    constructor() {
        this.user = null;
        this.init();
    }

    init() {
        this.checkSession();
        this.setupEventListeners();
        this.loadInitialData();
    }

    setupEventListeners() {
        // Logout
        document.addEventListener('click', (e) => {
            if (e.target.id === 'logout-btn') {
                this.logout();
            }
        });

        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'login-form') {
                e.preventDefault();
                this.login();
            }
        });

        // Navigation
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('nav-link') && e.target.dataset.page) {
                e.preventDefault();
                this.navigateTo(e.target.dataset.page);
            }
        });
    }

    async checkSession() {
        try {
            const response = await fetch(apiUrl('auth.php?action=check_session'));
            const data = await response.json();
            
            if (data.success) {
                this.user = data.data.user;
                this.updateUI();
            } else if (!window.location.pathname.includes('login.php')) {
                window.location.href = pageUrl('login.php');
            }
        } catch (error) {
            console.error('Error checking session:', error);
            if (!window.location.pathname.includes('login.php')) {
                window.location.href = pageUrl('login.php');
            }
        }
    }

    async login() {
        const form = document.getElementById('login-form');
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const btnText = submitBtn.innerHTML;

        try {
            submitBtn.innerHTML = '<span class="loading"></span> Entrando...';
            submitBtn.disabled = true;

            const response = await fetch(apiUrl('auth.php'), {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.user = data.data.user;
                this.showAlert('success', 'Login realizado com sucesso!');
                setTimeout(() => {
                    // Redirecionar admin para users.php em vez de dashboard.php
                    if (this.user.role === 'admin') {
                        window.location.href = pageUrl('users.php');
                    } else {
                        window.location.href = pageUrl('dashboard.php');
                    }
                }, 1000);
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('danger', 'Erro ao fazer login. Tente novamente.');
        } finally {
            submitBtn.innerHTML = btnText;
            submitBtn.disabled = false;
        }
    }

    async logout() {
        try {
            const response = await fetch(apiUrl('auth.php?action=logout'));
            const data = await response.json();

            if (data.success) {
                this.user = null;
                window.location.href = pageUrl('login.php');
            }
        } catch (error) {
            console.error('Logout error:', error);
            window.location.href = pageUrl('login.php');
        }
    }

    updateUI() {
        // Update user info in navbar
        const userInfo = document.getElementById('user-info');
        if (userInfo && this.user) {
            userInfo.textContent = this.user.full_name;
        }

        // Update navigation based on role
        this.updateNavigation();
    }

    updateNavigation() {
        if (!this.user) return;

        // Show/hide menu items based on user role
        const adminItems = document.querySelectorAll('.admin-only');
        adminItems.forEach(item => {
            item.style.display = this.user.role === 'admin' ? 'block' : 'none';
        });
    }

    navigateTo(page) {
        window.location.href = pageUrl(`${page}.php`);
    }

    loadInitialData() {
        // Load page-specific data
        const currentPage = this.getCurrentPage();
        
        switch (currentPage) {
            case 'dashboard':
                this.loadDashboardData();
                break;
            case 'products':
                this.loadProductsData();
                break;
            case 'reports':
                this.loadReportsData();
                break;
        }
    }

    getCurrentPage() {
        const path = window.location.pathname;
        const page = path.split('/').pop().replace('.php', '');
        return page;
    }

    async loadDashboardData() {
        try {
            const response = await fetch(apiUrl('reports.php?action=dashboard'));
            const data = await response.json();

            if (data.success) {
                this.updateDashboardStats(data.data);
                this.loadRecentSales();
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    updateDashboardStats(stats) {
        // Update today sales
        const todaySalesCount = document.getElementById('today-sales-count');
        const todaySalesAmount = document.getElementById('today-sales-amount');
        if (todaySalesCount) todaySalesCount.textContent = stats.today_sales.count;
        if (todaySalesAmount) todaySalesAmount.textContent = this.formatCurrency(stats.today_sales.total);

        // Update month sales
        const monthSalesCount = document.getElementById('month-sales-count');
        const monthSalesAmount = document.getElementById('month-sales-amount');
        if (monthSalesCount) monthSalesCount.textContent = stats.month_sales.count;
        if (monthSalesAmount) monthSalesAmount.textContent = this.formatCurrency(stats.month_sales.total);

        // Update products count
        const totalProducts = document.getElementById('total-products');
        if (totalProducts) totalProducts.textContent = stats.total_products;

        // Update low stock
        const lowStock = document.getElementById('low-stock');
        if (lowStock) lowStock.textContent = stats.low_stock;
    }

    async loadRecentSales() {
        try {
            const response = await fetch(apiUrl('sales.php?action=recent&limit=5'));
            const data = await response.json();

            if (data.success) {
                this.displayRecentSales(data.data);
            }
        } catch (error) {
            console.error('Error loading recent sales:', error);
        }
    }

    displayRecentSales(sales) {
        const container = document.getElementById('recent-sales');
        if (!container) return;

        if (sales.length === 0) {
            container.innerHTML = '<p class="text-muted">Nenhuma venda encontrada</p>';
            return;
        }

        const html = sales.map(sale => `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <strong>Venda #${sale.id}</strong><br>
                    <small class="text-muted">${sale.user_name} - ${this.formatDateTime(sale.created_at)}</small>
                </div>
                <div class="text-success font-weight-bold">
                    ${this.formatCurrency(sale.total_amount)}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    async loadProductsData() {
        const page = 1;
        const limit = 50;

        try {
            const response = await fetch(apiUrl(`products.php?page=${page}&limit=${limit}`));
            const data = await response.json();

            if (data.success) {
                this.displayProducts(data.data.products);
                this.setupProductsPagination(data.data);
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error loading products:', error);
            this.showAlert('danger', 'Erro ao carregar produtos');
        }
    }

    displayProducts(products) {
        const tbody = document.getElementById('products-tbody');
        if (!tbody) return;

        if (products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhum produto encontrado</td></tr>';
            return;
        }

        const html = products.map(product => `
            <tr>
                <td>${product.id}</td>
                <td>${product.name}</td>
                <td>${product.barcode || '-'}</td>
                <td>${product.category_name || '-'}</td>
                <td>${this.formatCurrency(product.price)}</td>
                <td>
                    <span class="badge ${product.stock_quantity <= product.min_stock ? 'badge-warning' : 'badge-success'}">
                        ${product.stock_quantity}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editProduct(${product.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        tbody.innerHTML = html;
    }

    setupProductsPagination(data) {
        // Implementation for pagination if needed
        const totalPages = Math.ceil(data.total / data.limit);
        // Add pagination controls here
    }

    showAlert(type, message, duration = 5000) {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;

        // Insert at top of main content
        const mainContent = document.querySelector('.main-content') || document.body;
        mainContent.insertBefore(alert, mainContent.firstChild);

        // Auto dismiss
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, duration);
    }

    formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    formatDateTime(dateString) {
        return new Intl.DateTimeFormat('pt-BR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(dateString));
    }

    formatDate(dateString) {
        return new Intl.DateTimeFormat('pt-BR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        }).format(new Date(dateString));
    }
}

// Initialize the system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.pdvSystem = new PDVSystem();
});

// Global functions for product management
function editProduct(id) {
    // Implementation for editing product
    console.log('Edit product:', id);
    // Open modal or navigate to edit page
}

function deleteProduct(id) {
    if (confirm('Tem certeza que deseja excluir este produto?')) {
        // Implementation for deleting product
        console.log('Delete product:', id);
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, '');
    if (cpf.length !== 11 || !!cpf.match(/(\d)\1{10}/)) return false;
    
    let sum = 0;
    let remainder;
    
    for (let i = 1; i <= 9; i++) {
        sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }
    
    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.substring(9, 10))) return false;
    
    sum = 0;
    for (let i = 1; i <= 10; i++) {
        sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }
    
    remainder = (sum * 10) % 11;
    if (remainder === 10 || remainder === 11) remainder = 0;
    if (remainder !== parseInt(cpf.substring(10, 11))) return false;
    
    return true;
}
