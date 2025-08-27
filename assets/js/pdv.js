// Sistema PDV - JavaScript específico para PDV
class PDVManager {
    constructor() {
        this.cart = [];
        this.total = 0;
        this.products = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadCategories();
        this.updateCartDisplay();
    }

    setupEventListeners() {
        // Product search
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.searchProducts(e.target.value);
            }, 300));

            // Hide search results when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.product-search')) {
                    this.hideSearchResults();
                }
            });
        }

        // Barcode search
        const barcodeInput = document.getElementById('barcode-search');
        if (barcodeInput) {
            barcodeInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchByBarcode(e.target.value);
                    e.target.value = '';
                }
            });
        }

        // Payment form
        const paymentForm = document.getElementById('payment-form');
        if (paymentForm) {
            paymentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.processSale();
            });
        }

        // Payment amount input
        const paymentAmountInput = document.getElementById('payment-amount');
        if (paymentAmountInput) {
            paymentAmountInput.addEventListener('input', (e) => {
                this.updateChange();
            });
        }

        // Clear cart button
        const clearCartBtn = document.getElementById('clear-cart');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => {
                this.clearCart();
            });
        }
    }

    async loadCategories() {
        try {
            const response = await fetch(apiUrl('products.php?action=categories'));
            const data = await response.json();

            if (data.success) {
                this.displayCategories(data.data);
            }
        } catch (error) {
            console.error('Error loading categories:', error);
        }
    }

    displayCategories(categories) {
        const container = document.getElementById('categories-container');
        if (!container) return;

        const html = categories.map(category => `
            <button class="btn btn-outline-primary btn-sm category-btn" 
                    onclick="pdvManager.filterByCategory(${category.id})">
                ${category.name}
            </button>
        `).join('');

        container.innerHTML = `
            <button class="btn btn-outline-secondary btn-sm category-btn active" 
                    onclick="pdvManager.clearCategoryFilter()">
                Todos
            </button>
            ${html}
        `;
    }

    async searchProducts(term) {
        if (term.length < 2) {
            this.hideSearchResults();
            return;
        }

        try {
            const response = await fetch(apiUrl(`products.php?action=search&term=${encodeURIComponent(term)}`));
            const data = await response.json();

            if (data.success) {
                this.displaySearchResults(data.data);
            }
        } catch (error) {
            console.error('Error searching products:', error);
        }
    }

    async searchByBarcode(barcode) {
        if (!barcode.trim()) return;

        try {
            const response = await fetch(apiUrl(`products.php?action=search&barcode=${encodeURIComponent(barcode)}`));
            const data = await response.json();

            if (data.success && data.data.length > 0) {
                this.addToCart(data.data[0]);
            } else {
                pdvSystem.showAlert('warning', 'Produto não encontrado');
            }
        } catch (error) {
            console.error('Error searching by barcode:', error);
            pdvSystem.showAlert('danger', 'Erro ao buscar produto');
        }
    }

    displaySearchResults(products) {
        const container = document.getElementById('search-results');
        if (!container) return;

        if (products.length === 0) {
            container.innerHTML = '<div class="search-result-item">Nenhum produto encontrado</div>';
            container.style.display = 'block';
            return;
        }

        const html = products.map(product => `
            <div class="search-result-item" onclick="pdvManager.addToCart(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${product.name}</strong><br>
                        <small class="text-muted">${product.barcode || 'Sem código'}</small>
                    </div>
                    <div class="text-primary font-weight-bold">
                        ${pdvSystem.formatCurrency(product.price)}
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
        container.style.display = 'block';
    }

    hideSearchResults() {
        const container = document.getElementById('search-results');
        if (container) {
            container.style.display = 'none';
        }
    }

    addToCart(product) {
        // Check if product already in cart
        const existingItem = this.cart.find(item => item.product_id === product.id);

        if (existingItem) {
            if (existingItem.quantity < product.stock_quantity) {
                existingItem.quantity++;
                existingItem.total = existingItem.quantity * existingItem.unit_price;
            } else {
                pdvSystem.showAlert('warning', 'Estoque insuficiente');
                return;
            }
        } else {
            if (product.stock_quantity <= 0) {
                pdvSystem.showAlert('warning', 'Produto sem estoque');
                return;
            }

            this.cart.push({
                product_id: product.id,
                name: product.name,
                unit_price: parseFloat(product.price),
                quantity: 1,
                total: parseFloat(product.price),
                stock_available: product.stock_quantity
            });
        }

        this.updateCartDisplay();
        this.hideSearchResults();

        // Clear search input
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.value = '';
        }

        pdvSystem.showAlert('success', `${product.name} adicionado ao carrinho`, 2000);
    }

    removeFromCart(index) {
        this.cart.splice(index, 1);
        this.updateCartDisplay();
    }

    updateCartQuantity(index, newQuantity) {
        const item = this.cart[index];
        
        if (newQuantity <= 0) {
            this.removeFromCart(index);
            return;
        }

        if (newQuantity > item.stock_available) {
            pdvSystem.showAlert('warning', 'Quantidade maior que o estoque disponível');
            return;
        }

        item.quantity = newQuantity;
        item.total = item.quantity * item.unit_price;
        this.updateCartDisplay();
    }

    updateCartDisplay() {
        this.calculateTotal();
        this.displayCartItems();
        this.displayCartSummary();
        this.updateChange();
    }

    calculateTotal() {
        this.total = this.cart.reduce((sum, item) => sum + item.total, 0);
    }

    displayCartItems() {
        const container = document.getElementById('cart-items');
        if (!container) return;

        if (this.cart.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">Carrinho vazio</p>';
            return;
        }

        const html = this.cart.map((item, index) => `
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.name}</h6>
                        <small class="text-muted">${pdvSystem.formatCurrency(item.unit_price)} cada</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="pdvManager.removeFromCart(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="input-group" style="width: 120px;">
                        <div class="input-group-prepend">
                            <button class="btn btn-outline-secondary btn-sm" type="button" 
                                    onclick="pdvManager.updateCartQuantity(${index}, ${item.quantity - 1})">-</button>
                        </div>
                        <input type="number" class="form-control form-control-sm text-center" 
                               value="${item.quantity}" min="1" max="${item.stock_available}"
                               onchange="pdvManager.updateCartQuantity(${index}, parseInt(this.value))">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary btn-sm" type="button" 
                                    onclick="pdvManager.updateCartQuantity(${index}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                    <strong class="text-primary">${pdvSystem.formatCurrency(item.total)}</strong>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    displayCartSummary() {
        const totalElement = document.getElementById('cart-total');
        if (totalElement) {
            totalElement.textContent = pdvSystem.formatCurrency(this.total);
        }

        const itemCountElement = document.getElementById('cart-item-count');
        if (itemCountElement) {
            const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
            itemCountElement.textContent = totalItems;
        }
    }

    updateChange() {
        const paymentAmountInput = document.getElementById('payment-amount');
        const changeElement = document.getElementById('change-amount');
        
        if (!paymentAmountInput || !changeElement) return;

        const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
        const change = paymentAmount - this.total;

        changeElement.textContent = pdvSystem.formatCurrency(Math.max(0, change));
        changeElement.className = change >= 0 ? 'text-success' : 'text-danger';

        // Update process sale button state
        const processSaleBtn = document.getElementById('process-sale-btn');
        if (processSaleBtn) {
            processSaleBtn.disabled = this.cart.length === 0 || change < 0;
        }
    }

    clearCart() {
        if (this.cart.length === 0) return;

        if (confirm('Tem certeza que deseja limpar o carrinho?')) {
            this.cart = [];
            this.updateCartDisplay();
            pdvSystem.showAlert('info', 'Carrinho limpo');
        }
    }

    async processSale() {
        if (this.cart.length === 0) {
            pdvSystem.showAlert('warning', 'Adicione produtos ao carrinho');
            return;
        }

        const paymentMethod = document.getElementById('payment-method').value;
        const paymentAmount = parseFloat(document.getElementById('payment-amount').value) || 0;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const notes = document.getElementById('sale-notes').value;

        if (!paymentMethod) {
            pdvSystem.showAlert('warning', 'Selecione o método de pagamento');
            return;
        }

        if (paymentAmount < this.total) {
            pdvSystem.showAlert('warning', 'Valor do pagamento insuficiente');
            return;
        }

        const processSaleBtn = document.getElementById('process-sale-btn');
        const btnText = processSaleBtn.innerHTML;

        try {
            processSaleBtn.innerHTML = '<span class="loading"></span> Processando...';
            processSaleBtn.disabled = true;

            const saleData = {
                items: this.cart,
                payment_method: paymentMethod,
                payment_amount: paymentAmount,
                discount: discount,
                tax: 0, // Can be calculated if needed
                notes: notes
            };

            const response = await fetch(apiUrl('sales.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(saleData)
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();
            let data;
            
            try {
                data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Resposta não é JSON válido:', responseText);
                throw new Error('Erro na comunicação com o servidor. Verifique os logs.');
            }

            if (data.success) {
                pdvSystem.showAlert('success', 'Venda realizada com sucesso!');
                this.showSaleReceipt(data.data);
                this.resetSale();
            } else {
                pdvSystem.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error processing sale:', error);
            pdvSystem.showAlert('danger', 'Erro ao processar venda');
        } finally {
            processSaleBtn.innerHTML = btnText;
            processSaleBtn.disabled = false;
        }
    }

    showSaleReceipt(saleData) {
        const modal = document.getElementById('receipt-modal');
        if (!modal) return;

        const receiptContent = document.getElementById('receipt-content');
        const change = saleData.change_amount || 0;

        receiptContent.innerHTML = `
            <div class="text-center mb-3">
                <h5>COMPROVANTE DE VENDA</h5>
                <p class="mb-1">Venda #${saleData.sale_id}</p>
                <p class="text-muted">${pdvSystem.formatDateTime(new Date())}</p>
            </div>
            
            <div class="border-top border-bottom py-2 mb-3">
                ${this.cart.map(item => `
                    <div class="d-flex justify-content-between">
                        <span>${item.name}</span>
                        <span>${item.quantity}x ${pdvSystem.formatCurrency(item.unit_price)}</span>
                    </div>
                `).join('')}
            </div>
            
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <strong>TOTAL:</strong>
                    <strong>${pdvSystem.formatCurrency(saleData.total_amount)}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Pago:</span>
                    <span>${pdvSystem.formatCurrency(saleData.payment_amount || 0)}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Troco:</span>
                    <span>${pdvSystem.formatCurrency(change)}</span>
                </div>
            </div>
            
            <div class="text-center">
                <p class="mb-0">Obrigado pela preferência!</p>
            </div>
        `;

        // Show modal (Bootstrap)
        if (window.$ && window.$.fn.modal) {
            $(modal).modal('show');
        } else {
            modal.style.display = 'block';
        }
    }

    resetSale() {
        this.cart = [];
        this.updateCartDisplay();

        // Reset form
        const form = document.getElementById('payment-form');
        if (form) {
            form.reset();
        }

        // Focus on search input
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.focus();
        }
    }

    filterByCategory(categoryId) {
        // Update active category button
        const categoryBtns = document.querySelectorAll('.category-btn');
        categoryBtns.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // Implementation for filtering products by category
        console.log('Filter by category:', categoryId);
    }

    clearCategoryFilter() {
        // Update active category button
        const categoryBtns = document.querySelectorAll('.category-btn');
        categoryBtns.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');

        // Clear filter
        console.log('Clear category filter');
    }
}

// Initialize PDV manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('pdv-container')) {
        window.pdvManager = new PDVManager();
    }
});
