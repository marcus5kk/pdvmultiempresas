<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;

    public function __construct() {
        // Use environment variables with fallbacks para MySQL
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_DATABASE'] ?? 'pdv_system';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
        $this->port = $_ENV['DB_PORT'] ?? '3306';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
            
            // Verificar conexão
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Configurar charset para UTF-8
            $this->conn->set_charset("utf8mb4");
            
        } catch(Exception $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
        
        return $this->conn;
    }

    // Método para preparar statements compatível com PDO
    public function prepare($sql) {
        return new MySQLiStatementWrapper($this->conn->prepare($sql));
    }

    // Método para iniciar transação
    public function beginTransaction() {
        return $this->conn->autocommit(false);
    }

    // Método para commit
    public function commit() {
        $result = $this->conn->commit();
        $this->conn->autocommit(true);
        return $result;
    }

    // Método para rollback
    public function rollBack() {
        $result = $this->conn->rollback();
        $this->conn->autocommit(true);
        return $result;
    }
}

// Classe para fazer o MySQLi statement compatível com PDO
class MySQLiStatementWrapper {
    private $stmt;
    private $result;

    public function __construct($stmt) {
        $this->stmt = $stmt;
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $types = '';
            $values = [];
            
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $param;
            }
            
            $this->stmt->bind_param($types, ...$values);
        }
        
        $result = $this->stmt->execute();
        $this->result = $this->stmt->get_result();
        return $result;
    }

    public function fetch() {
        if ($this->result) {
            return $this->result->fetch_assoc();
        }
        return false;
    }

    public function fetchAll() {
        if ($this->result) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function rowCount() {
        return $this->stmt->affected_rows;
    }

    // Para queries INSERT que precisam retornar o ID
    public function lastInsertId() {
        return $this->stmt->insert_id;
    }
}
?>