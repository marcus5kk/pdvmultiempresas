SISTEMA PDV - INSTRUÇÕES DE INSTALAÇÃO
=========================================

PARA USAR NO REPLIT (atual):
- O sistema já está configurado e rodando
- Login: admin / Senha: password
- Usa PostgreSQL automaticamente

PARA USAR EM HOSPEDAGEM PRÓPRIA (MySQL):
==========================================

1. CONFIGURAÇÃO DO BANCO:
   - Crie um banco MySQL chamado "pdv_system" 
   - Importe o arquivo: database/schema_mysql.sql
   - Configure as variáveis no arquivo config/config.php:
     
     DB_TYPE = 'mysql'
     DB_HOST = 'localhost'
     DB_DATABASE = 'pdv_system'
     DB_USERNAME = 'seu_usuario'
     DB_PASSWORD = 'sua_senha'

2. INSTALAÇÃO:
   - Faça upload de todos os arquivos para sua hospedagem
   - Pode ser na raiz ou em qualquer subdiretório (ex: /pdv)
   - O sistema detecta automaticamente onde está instalado

3. CONFIGURAÇÃO INICIAL:
   - Acesse o sistema pelo navegador
   - Login padrão: admin / password
   - Altere a senha após o primeiro acesso

4. ESTRUTURA DE ARQUIVOS:
   /api/          - Endpoints da API
   /assets/       - CSS, JS e imagens
   /config/       - Configurações do sistema
   /database/     - Arquivos SQL
   /includes/     - Arquivos compartilhados
   /pages/        - Páginas do sistema

5. FUNCIONALIDADES:
   - Sistema de login com sessões
   - Dashboard com estatísticas
   - Gerenciamento de produtos
   - Processamento de vendas (PDV)
   - Relatórios com gráficos
   - Controle automático de estoque

6. REQUISITOS:
   - PHP 7.4 ou superior
   - MySQL 5.7 ou superior (ou PostgreSQL)
   - Extensões PHP: PDO, MySQLi, JSON, Session

SUPORTE:
- O sistema é responsivo e funciona em desktop e mobile
- Todos os caminhos são dinâmicos e funcionam em qualquer diretório
- Configuração automática de banco (PostgreSQL ou MySQL)