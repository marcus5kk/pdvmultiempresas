class UsersManager {
    constructor() {
        this.init();
    }

    init() {
        this.loadCompanies();
        this.loadUsers();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Add company form
        const addCompanyForm = document.getElementById('add-company-form');
        if (addCompanyForm) {
            addCompanyForm.addEventListener('submit', (e) => this.handleAddCompany(e));
        }

        // Add user form
        const addUserForm = document.getElementById('add-user-form');
        if (addUserForm) {
            addUserForm.addEventListener('submit', (e) => this.handleAddUser(e));
        }

        // Company filter
        const companyFilter = document.getElementById('company-filter');
        if (companyFilter) {
            companyFilter.addEventListener('change', () => this.filterUsersByCompany());
        }
    }

    async loadCompanies() {
        try {
            const response = await fetch(apiUrl('companies.php?action=list'));
            const data = await response.json();

            if (data.success) {
                this.renderCompaniesTable(data.data);
                this.populateCompanySelects(data.data);
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error loading companies:', error);
            this.showAlert('danger', 'Erro ao carregar empresas');
        }
    }

    async loadUsers() {
        try {
            const response = await fetch(apiUrl('users.php?action=list'));
            const data = await response.json();

            if (data.success) {
                this.renderUsersTable(data.data);
                this.allUsers = data.data; // Store for filtering
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.showAlert('danger', 'Erro ao carregar usuários');
        }
    }

    renderCompaniesTable(companies) {
        const tbody = document.querySelector('#companies-table tbody');
        if (!tbody) return;

        if (companies.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nenhuma empresa encontrada</td></tr>';
            return;
        }

        tbody.innerHTML = companies.map(company => `
            <tr>
                <td>${company.id}</td>
                <td>${this.escapeHtml(company.name)}</td>
                <td>${company.document || '-'}</td>
                <td>${company.email || '-'}</td>
                <td>
                    <span class="badge ${company.active ? 'bg-success' : 'bg-danger'}">
                        ${company.active ? 'Ativa' : 'Inativa'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="usersManager.showCompanyUsers(${company.id})">
                        <i class="fas fa-users"></i> Ver Usuários
                    </button>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-warning" onclick="usersManager.editCompany(${company.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm ${company.active ? 'btn-danger' : 'btn-success'}" 
                                onclick="usersManager.toggleCompanyStatus(${company.id}, ${!company.active})" 
                                title="${company.active ? 'Desativar' : 'Ativar'}">
                            <i class="fas ${company.active ? 'fa-ban' : 'fa-check'}"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    renderUsersTable(users) {
        const tbody = document.querySelector('#users-table tbody');
        if (!tbody) return;

        if (users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Nenhum usuário encontrado</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(user => `
            <tr>
                <td>${user.id}</td>
                <td>${this.escapeHtml(user.full_name)}</td>
                <td>${this.escapeHtml(user.username)}</td>
                <td>${user.email || '-'}</td>
                <td>${user.company_name || '-'}</td>
                <td>
                    <span class="badge ${this.getRoleBadgeClass(user.role)}">
                        ${this.getRoleDisplayName(user.role)}
                    </span>
                </td>
                <td>
                    <span class="badge ${user.active ? 'bg-success' : 'bg-danger'}">
                        ${user.active ? 'Ativo' : 'Inativo'}
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-warning" onclick="usersManager.editUser(${user.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${user.role !== 'system_admin' ? `
                            <button class="btn btn-sm ${user.active ? 'btn-danger' : 'btn-success'}" 
                                    onclick="usersManager.toggleUserStatus(${user.id}, ${!user.active})" 
                                    title="${user.active ? 'Desativar' : 'Ativar'}">
                                <i class="fas ${user.active ? 'fa-ban' : 'fa-check'}"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    populateCompanySelects(companies) {
        const selects = ['user-company', 'company-filter'];
        
        selects.forEach(selectId => {
            const select = document.getElementById(selectId);
            if (select) {
                // Keep the first option (placeholder)
                const firstOption = select.querySelector('option[value=""]');
                select.innerHTML = '';
                if (firstOption) {
                    select.appendChild(firstOption.cloneNode(true));
                }
                
                companies.forEach(company => {
                    if (company.active) {
                        const option = document.createElement('option');
                        option.value = company.id;
                        option.textContent = company.name;
                        select.appendChild(option);
                    }
                });
            }
        });
    }

    async handleAddCompany(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'create');
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
            submitBtn.disabled = true;
            
            const response = await fetch(apiUrl('companies.php'), {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('success', 'Empresa criada com sucesso!');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('addCompanyModal')).hide();
                this.loadCompanies();
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error creating company:', error);
            this.showAlert('danger', 'Erro ao criar empresa');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    async handleAddUser(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'create');
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        try {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';
            submitBtn.disabled = true;
            
            const response = await fetch(apiUrl('users.php'), {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('success', 'Usuário criado com sucesso!');
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                this.loadUsers();
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error creating user:', error);
            this.showAlert('danger', 'Erro ao criar usuário');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    filterUsersByCompany() {
        const companyFilter = document.getElementById('company-filter');
        const selectedCompanyId = companyFilter.value;
        
        if (!this.allUsers) return;
        
        let filteredUsers = this.allUsers;
        if (selectedCompanyId) {
            filteredUsers = this.allUsers.filter(user => user.company_id == selectedCompanyId);
        }
        
        this.renderUsersTable(filteredUsers);
    }

    showCompanyUsers(companyId) {
        const companyFilter = document.getElementById('company-filter');
        companyFilter.value = companyId;
        this.filterUsersByCompany();
        
        // Scroll to users section
        document.querySelector('#users-table').scrollIntoView({ behavior: 'smooth' });
    }

    async toggleCompanyStatus(companyId, newStatus) {
        if (!confirm(`Tem certeza que deseja ${newStatus ? 'ativar' : 'desativar'} esta empresa?`)) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', companyId);
            formData.append('active', newStatus);
            
            const response = await fetch(apiUrl('companies.php'), {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('success', `Empresa ${newStatus ? 'ativada' : 'desativada'} com sucesso!`);
                this.loadCompanies();
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error toggling company status:', error);
            this.showAlert('danger', 'Erro ao alterar status da empresa');
        }
    }

    async toggleUserStatus(userId, newStatus) {
        if (!confirm(`Tem certeza que deseja ${newStatus ? 'ativar' : 'desativar'} este usuário?`)) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', userId);
            formData.append('active', newStatus);
            
            const response = await fetch(apiUrl('users.php'), {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showAlert('success', `Usuário ${newStatus ? 'ativado' : 'desativado'} com sucesso!`);
                this.loadUsers();
            } else {
                this.showAlert('danger', data.message);
            }
        } catch (error) {
            console.error('Error toggling user status:', error);
            this.showAlert('danger', 'Erro ao alterar status do usuário');
        }
    }

    getRoleBadgeClass(role) {
        const classes = {
            'system_admin': 'bg-danger',
            'company_admin': 'bg-primary',
            'company_operator': 'bg-info'
        };
        return classes[role] || 'bg-secondary';
    }

    getRoleDisplayName(role) {
        const names = {
            'system_admin': 'Admin Sistema',
            'company_admin': 'Admin Empresa',
            'company_operator': 'Operador'
        };
        return names[role] || role;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showAlert(type, message) {
        const alertContainer = document.getElementById('alert-container');
        if (!alertContainer) return;

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        alertContainer.appendChild(alertDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('users.php')) {
        window.usersManager = new UsersManager();
    }
});