<!DOCTYPE html>
<html>
<head>
    <title>Debug Login Real vs Teste</title>
    <meta charset="utf-8">
</head>
<body>
    <h2>Comparação: Login Real vs Teste</h2>
    
    <div id="resultado"></div>
    
    <script src="assets/js/config.js"></script>
    <script>
        function log(msg) {
            document.getElementById('resultado').innerHTML += '<p>' + msg + '</p>';
        }
        
        window.onload = function() {
            log('🔍 DIAGNÓSTICO DE CAMINHOS');
            log('');
            
            // Verificar funções do config.js
            if (typeof apiUrl === 'function') {
                log('✅ Função apiUrl existe');
                log('📍 apiUrl("auth.php"): ' + apiUrl('auth.php'));
                log('📍 apiUrl("auth.php?action=check_session"): ' + apiUrl('auth.php?action=check_session'));
            } else {
                log('❌ Função apiUrl NÃO existe!');
            }
            
            if (typeof pageUrl === 'function') {
                log('✅ Função pageUrl existe');  
                log('📍 pageUrl("dashboard.php"): ' + pageUrl('dashboard.php'));
                log('📍 pageUrl("login.php"): ' + pageUrl('login.php'));
            } else {
                log('❌ Função pageUrl NÃO existe!');
            }
            
            log('');
            log('🧪 TESTE COMPARATIVO');
            
            // Testar login igual ao sistema real
            log('🔄 Testando login com mesmo código da página real...');
            
            setTimeout(testarLoginReal, 1000);
        };
        
        async function testarLoginReal() {
            try {
                // Simular FormData igual ao sistema real
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('username', 'admin');
                formData.append('password', 'password');
                
                log('📡 Chamando: ' + apiUrl('auth.php'));
                
                const response = await fetch(apiUrl('auth.php'), {
                    method: 'POST',
                    body: formData
                });
                
                log('📶 Status HTTP: ' + response.status);
                log('📶 Response OK: ' + response.ok);
                
                const data = await response.json();
                
                if (data.success) {
                    log('✅ LOGIN REAL FUNCIONOU!');
                    log('👤 Usuário: ' + JSON.stringify(data.data.user));
                    
                    // Testar redirecionamento
                    log('🔄 URL de redirecionamento: ' + pageUrl('dashboard.php'));
                    
                } else {
                    log('❌ LOGIN REAL FALHOU: ' + data.message);
                }
                
            } catch (error) {
                log('❌ ERRO no login real: ' + error.message);
            }
            
            log('');
            log('💡 COMPARAÇÃO COM TESTE:');
            log('🧪 Teste isolado: funciona');
            log('🏠 Página real: ' + (typeof apiUrl === 'function' ? 'usa apiUrl()' : 'sem config'));
            log('');
            log('🎯 Se funcionou acima mas não funciona na página, pode ser:');
            log('1. JavaScript carregando antes do DOM');
            log('2. Conflito entre bibliotecas (Bootstrap vs código)');  
            log('3. Cache do navegador específico da página login');
            log('4. Event listener não funcionando');
        }
    </script>
</body>
</html>