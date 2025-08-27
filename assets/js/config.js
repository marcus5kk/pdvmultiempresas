// Configuração FIXA para hospedagem
// Versão simplificada que sempre funciona

// Detectar ambiente
function isReplit() {
    return window.location.hostname.includes('replit.app') || 
           window.location.hostname.includes('repl.co');
}

// Configuração mais robusta
function createPDVConfig() {
    let basePath = '/';
    
    try {
        const currentPath = window.location.pathname;
        
        // Se estamos em uma página dentro de /pages/, ajustar para voltar à raiz
        if (currentPath.includes('/pages/')) {
            const pagesIndex = currentPath.indexOf('/pages/');
            basePath = currentPath.substring(0, pagesIndex);
            if (!basePath) basePath = '/';
            if (!basePath.endsWith('/')) basePath += '/';
        }
        // Se estamos na raiz de um subdiretório
        else if (currentPath !== '/' && !currentPath.includes('.php')) {
            basePath = currentPath;
            if (!basePath.endsWith('/')) basePath += '/';
        }
    } catch (e) {
        console.warn('Erro na detecção de caminho, usando padrão:', e);
        basePath = '/';
    }
    
    return {
        basePath: basePath,
        apiPath: function() {
            // Para páginas em /pages/, sempre usar ../api/
            if (window.location.pathname.includes('/pages/')) {
                return '../api/';
            }
            return this.basePath === '/' ? 'api/' : this.basePath + 'api/';
        },
        pagesPath: function() {
            // Para páginas em /pages/, usar relativo
            if (window.location.pathname.includes('/pages/')) {
                return './';
            }
            return this.basePath === '/' ? 'pages/' : this.basePath + 'pages/';
        },
        assetsPath: function() {
            // Para páginas em /pages/, sempre usar ../assets/
            if (window.location.pathname.includes('/pages/')) {
                return '../assets/';
            }
            return this.basePath === '/' ? 'assets/' : this.basePath + 'assets/';
        }
    };
}

// Criar configuração global com proteção
try {
    window.PDV_CONFIG = createPDVConfig();
    
    // Debug para hospedagem
    console.log('PDV Config criado:', {
        basePath: window.PDV_CONFIG.basePath,
        apiPath: window.PDV_CONFIG.apiPath(),
        pagesPath: window.PDV_CONFIG.pagesPath()
    });
    
} catch (error) {
    console.error('Erro criando PDV_CONFIG:', error);
    
    // Fallback absoluto - configuração mínima que sempre funciona
    window.PDV_CONFIG = {
        basePath: '/',
        apiPath: function() { 
            return window.location.pathname.includes('/pages/') ? '../api/' : 'api/'; 
        },
        pagesPath: function() { 
            return window.location.pathname.includes('/pages/') ? './' : 'pages/'; 
        },
        assetsPath: function() { 
            return window.location.pathname.includes('/pages/') ? '../assets/' : 'assets/'; 
        }
    };
}

// Funções utilitárias com proteção
function apiUrl(endpoint) {
    try {
        return window.PDV_CONFIG.apiPath() + endpoint;
    } catch (e) {
        console.warn('Erro apiUrl, usando fallback:', e);
        return (window.location.pathname.includes('/pages/') ? '../api/' : 'api/') + endpoint;
    }
}

function pageUrl(page) {
    try {
        return window.PDV_CONFIG.pagesPath() + page;
    } catch (e) {
        console.warn('Erro pageUrl, usando fallback:', e);
        return 'pages/' + page;
    }
}

function assetUrl(asset) {
    try {
        return window.PDV_CONFIG.assetsPath() + asset;
    } catch (e) {
        console.warn('Erro assetUrl, usando fallback:', e);
        return 'assets/' + asset;
    }
}