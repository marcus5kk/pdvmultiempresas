-- Sistema PDV - Schema MySQL
-- Execute este arquivo para criar as tabelas necessárias no MySQL

-- Criar database (executar separadamente se necessário)
-- CREATE DATABASE pdv_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE pdv_system;

-- Tabela de empresas
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    document VARCHAR(20) UNIQUE,
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(2),
    zipcode VARCHAR(10),
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(20) DEFAULT 'company_operator',
    company_id INT,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- Tabela de categorias de produtos
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de produtos
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    barcode VARCHAR(50),
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    cost_price DECIMAL(10,2),
    stock_quantity INT DEFAULT 0,
    min_stock INT DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'un',
    active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    UNIQUE KEY unique_barcode_company (barcode, company_id)
);

-- Tabela de vendas
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    change_amount DECIMAL(10,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabela de itens da venda
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Tabela de movimentação de estoque
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type VARCHAR(20) NOT NULL COMMENT 'in, out, adjustment',
    quantity INT NOT NULL,
    reference_type VARCHAR(50) COMMENT 'sale, purchase, adjustment',
    reference_id INT,
    notes TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Inserir empresa principal
INSERT IGNORE INTO companies (id, name, document, email, active) VALUES 
(1, 'Sistema Principal', '00000000000000', 'admin@sistema.com', true);

-- Inserir usuário padrão (admin/password)
INSERT IGNORE INTO users (username, password, full_name, role, company_id) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'system_admin', NULL);

-- Inserir algumas categorias padrão
INSERT IGNORE INTO categories (name, description) VALUES 
('Bebidas', 'Refrigerantes, sucos e águas'),
('Alimentação', 'Produtos alimentícios em geral'),
('Limpeza', 'Produtos de limpeza e higiene'),
('Eletrônicos', 'Produtos eletrônicos e acessórios');

-- Inserir alguns produtos de exemplo para a empresa principal
INSERT IGNORE INTO products (company_id, name, barcode, description, category_id, price, cost_price, stock_quantity, min_stock, unit) VALUES
(1, 'Coca-Cola 350ml', '7891991010252', 'Refrigerante Coca-Cola lata 350ml', 1, 4.50, 3.00, 100, 10, 'un'),
(1, 'Água Mineral 500ml', '7896098100014', 'Água mineral natural 500ml', 1, 2.00, 1.20, 150, 20, 'un'),
(1, 'Pão de Açúcar', '7891234567890', 'Pão de açúcar tradicional', 2, 8.50, 6.00, 50, 5, 'un'),
(1, 'Detergente Ypê', '7896036094044', 'Detergente líquido neutro 500ml', 3, 3.80, 2.50, 80, 10, 'un'),
(1, 'Sabonete Dove', '7891150047354', 'Sabonete hidratante Dove 90g', 3, 4.90, 3.20, 60, 8, 'un'),
(1, 'Fone de Ouvido', '1234567890123', 'Fone de ouvido com fio P2', 4, 25.00, 15.00, 30, 5, 'un');

-- Índices para melhor performance
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_sales_created_at ON sales(created_at);
CREATE INDEX idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX idx_stock_movements_product_id ON stock_movements(product_id);