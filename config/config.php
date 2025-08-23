<?php
// Configuração do sistema PDV
// Este arquivo permite que o sistema funcione em qualquer diretório

// Detectar o diretório base automaticamente
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    // Remover o nome do arquivo para obter o diretório
    $path = dirname($script_name);
    
    // Se estivermos em uma subpasta (como pages), voltar um nível
    if (basename($path) === 'pages') {
        $path = dirname($path);
    }
    
    // Se estivermos em config, voltar um nível também
    if (basename($path) === 'config') {
        $path = dirname($path);
    }
    
    // Normalizar o caminho
    $path = str_replace('\\', '/', $path);
    
    // Se estamos na raiz, path será /
    if ($path === '.' || $path === '') {
        $path = '/';
    }
    
    // Garantir que termine com / se não for apenas /
    if ($path !== '/' && substr($path, -1) !== '/') {
        $path .= '/';
    }
    
    return $protocol . $host . $path;
}

// Detectar o caminho físico base
function getBasePath() {
    $current_dir = __DIR__;
    return dirname($current_dir) . '/';
}

// URLs base do sistema
define('BASE_URL', getBaseUrl());
define('BASE_PATH', getBasePath());

// Configurações do banco de dados
// Para PostgreSQL (padrão do Replit)
define('DB_TYPE', $_ENV['DB_TYPE'] ?? 'postgresql');

// Para MySQL/MySQLi (quando usar em hospedagem própria)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_DATABASE', $_ENV['DB_DATABASE'] ?? 'pdv_system');
define('DB_USERNAME', $_ENV['DB_USERNAME'] ?? 'root');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');

// Configurações de sessão
define('SESSION_TIMEOUT', 3600); // 1 hora

// Configurações do sistema
define('SYSTEM_NAME', 'Sistema PDV');
define('SYSTEM_VERSION', '1.0');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro (desabilitar em produção)
if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Função para obter URL completa
function url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

// Função para obter caminho físico completo
function path($path = '') {
    return BASE_PATH . ltrim($path, '/');
}
?>