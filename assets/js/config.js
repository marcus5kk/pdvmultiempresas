// Configuração de caminhos dinâmicos
// Detecta automaticamente o diretório base do sistema

function detectBasePath() {
    // Método mais confiável: usar o caminho do script config.js
    const scripts = document.getElementsByTagName('script');
    for (let script of scripts) {
        if (script.src && script.src.includes('assets/js/config.js')) {
            const scriptUrl = new URL(script.src);
            const scriptPath = scriptUrl.pathname;
            // Remove '/assets/js/config.js' para obter o caminho base
            const basePath = scriptPath.replace('/assets/js/config.js', '');
            return basePath || '/';
        }
    }
    
    // Fallback: detectar pelo caminho atual da página
    const currentPath = window.location.pathname;
    
    // Se estamos em uma página dentro de /pages/, o base é um nível acima
    if (currentPath.includes('/pages/')) {
        const parts = currentPath.split('/pages/');
        return parts[0] || '/';
    }
    
    // Se estamos na raiz ou em outro diretório
    const pathParts = currentPath.split('/');
    pathParts.pop(); // Remove o arquivo atual
    
    let basePath = pathParts.join('/');
    if (basePath && !basePath.endsWith('/')) {
        basePath += '/';
    }
    
    return basePath || '/';
}

// Configuração global do sistema
window.PDV_CONFIG = {
    basePath: detectBasePath(),
    apiPath: function() {
        let base = this.basePath;
        if (base === '/') {
            return '/api/';
        }
        return base + (base.endsWith('/') ? '' : '/') + 'api/';
    },
    assetsPath: function() {
        let base = this.basePath;
        if (base === '/') {
            return '/assets/';
        }
        return base + (base.endsWith('/') ? '' : '/') + 'assets/';
    },
    pagesPath: function() {
        let base = this.basePath;
        if (base === '/') {
            return '/pages/';
        }
        return base + (base.endsWith('/') ? '' : '/') + 'pages/';
    }
};

// Debug para verificar os caminhos detectados
console.log('PDV Config:', {
    basePath: window.PDV_CONFIG.basePath,
    apiPath: window.PDV_CONFIG.apiPath(),
    pagesPath: window.PDV_CONFIG.pagesPath(),
    currentLocation: window.location.pathname
});

// Função utilitária para construir URLs da API
function apiUrl(endpoint) {
    return window.PDV_CONFIG.apiPath() + endpoint;
}

// Função utilitária para construir URLs de páginas
function pageUrl(page) {
    return window.PDV_CONFIG.pagesPath() + page;
}

// Função utilitária para construir URLs de assets
function assetUrl(asset) {
    return window.PDV_CONFIG.assetsPath() + asset;
}