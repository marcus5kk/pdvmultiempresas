<?php
// Arquivo para testar conexão MySQLi na hospedagem
// IMPORTANTE: Delete este arquivo após testar por segurança!

// Suas configurações de banco (substitua pelos dados reais)
$host = 'localhost';           // Host do seu provedor
$database = 'seu_database';    // Nome do banco
$username = 'seu_usuario';     // Usuário do banco  
$password = 'sua_senha';       // Senha do banco
$port = 3306;                  // Porta (geralmente 3306)

echo "<h2>Teste de Conexão MySQLi</h2>";

// Testar extensões necessárias
echo "<h3>1. Verificando extensões PHP:</h3>";
echo "MySQLi: " . (extension_loaded('mysqli') ? '✓ Disponível' : '✗ Não disponível') . "<br>";
echo "PDO: " . (extension_loaded('pdo') ? '✓ Disponível' : '✗ Não disponível') . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✓ Disponível' : '✗ Não disponível') . "<br><br>";

// Testar conexão
echo "<h3>2. Testando conexão:</h3>";

try {
    $conn = new mysqli($host, $username, $password, $database, $port);
    
    if ($conn->connect_error) {
        echo "✗ Erro de conexão: " . $conn->connect_error . "<br>";
    } else {
        echo "✓ Conexão MySQLi bem sucedida!<br>";
        echo "Versão MySQL: " . $conn->server_info . "<br>";
        echo "Charset: " . $conn->character_set_name() . "<br>";
        
        // Testar se consegue executar uma query simples
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            $num_tables = $result->num_rows;
            echo "Tabelas encontradas: " . $num_tables . "<br>";
            
            if ($num_tables > 0) {
                echo "<h4>Tabelas no banco:</h4>";
                while ($row = $result->fetch_array()) {
                    echo "- " . $row[0] . "<br>";
                }
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "<br>";
}

echo "<br><strong style='color: red;'>IMPORTANTE: Apague este arquivo (teste_mysql.php) após o teste por segurança!</strong>";
?>