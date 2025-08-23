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
            
            // Se basePath está vazio, estamos na raiz
            if (!basePath || basePath === '') {
                return '/';
            }
            
            // Garantir que termine com /
            if (!basePath.endsWith('/')) {
                basePath += '/';
            }
            
            return basePath;
        }
    }
    
    // Fallback: detectar pelo caminho atual da página
    const currentPath = window.location.pathname;
    
    // Se estamos em uma página dentro de /pages/, o base é um nível acima
    if (currentPath.includes('/pages/')) {
        // Encontrar a posição de /pages/ e pegar tudo antes
        const pagesIndex = currentPath.lastIndexOf('/pages/');
        if (pagesIndex >= 0) {
            let basePath = currentPath.substring(0, pagesIndex);
            
            // Se basePath está vazio, estamos na raiz
            if (!basePath || basePath === '') {
                return '/';
            }
            
            // Garantir que termine com /
            if (!basePath.endsWith('/')) {
                basePath += '/';
            }
            
            return basePath;
        }
    }
    
    // Se não conseguiu detectar, assumir raiz
    return '/';
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

// Debug removido para produção
// Para debug, descomente a linha abaixo:
// console.log('PDV Config:', window.PDV_CONFIG);

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