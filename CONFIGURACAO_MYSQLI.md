# Configuração para Hospedagem MySQLi

## Passos para configurar o sistema na sua hospedagem MySQLi:

### 1. Configurar as variáveis de ambiente no config.php

Altere apenas estas linhas no arquivo `config/config.php`:

```php
// Mudar de 'postgresql' para 'mysql'
define('DB_TYPE', 'mysql');

// Configurar dados da sua hospedagem
define('DB_HOST', 'localhost');  // ou o host do seu provedor
define('DB_DATABASE', 'seu_database_mysqli');
define('DB_USERNAME', 'seu_usuario_mysql');
define('DB_PASSWORD', 'sua_senha_mysql');
define('DB_PORT', '3306');
```

### 2. Criar o banco de dados

Execute o arquivo `database/schema_mysql.sql` no seu phpMyAdmin ou cliente MySQL.

### 3. Verificar se sua hospedagem tem as extensões PHP necessárias

Sua hospedagem precisa ter instalado:
- mysqli (geralmente já vem instalado)
- php_pdo (opcional, mas recomendado)

### 4. Testar a conexão

Após fazer as alterações, acesse o sistema. Se der erro de conexão:

1. Verifique se os dados de conexão estão corretos
2. Verifique se o banco de dados foi criado
3. Verifique se o usuário MySQL tem permissões no banco

### 5. Login padrão
- Usuário: admin
- Senha: password

## Estrutura de arquivos que funciona nos dois ambientes:

- `config/database.php` - Detecta automaticamente se é MySQL ou PostgreSQL
- `database/schema.sql` - Para PostgreSQL (Replit)  
- `database/schema_mysql.sql` - Para MySQL (hospedagem)

O sistema foi projetado para funcionar em ambos os ambientes sem precisar alterar código PHP!