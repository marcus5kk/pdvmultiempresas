// Configuração de caminhos dinâmicos
// Detecta automaticamente o diretório base do sistema

function detectBasePath() {
    const currentPath = window.location.pathname;
    const scriptElements = document.getElementsByTagName('script');
    
    // Procurar pelo script config.js para determinar o caminho base
    for (let script of scriptElements) {
        if (script.src && script.src.includes('assets/js/config.js')) {
            const scriptPath = script.src;
            const baseUrl = scriptPath.replace('/assets/js/config.js', '');
            return baseUrl.replace(window.location.origin, '');
        }
    }
    
    // Fallback: detectar pelo caminho atual
    const pathParts = currentPath.split('/');
    pathParts.pop(); // Remove o nome da página atual
    
    // Se estiver na pasta pages, voltar um nível
    if (pathParts[pathParts.length - 1] === 'pages') {
        pathParts.pop();
    }
    
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
        return this.basePath + 'api/';
    },
    assetsPath: function() {
        return this.basePath + 'assets/';
    },
    pagesPath: function() {
        return this.basePath + 'pages/';
    }
};

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