<?php
// Incluir configurações
require_once __DIR__ . '/config.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;
    private $db_type;

    public function __construct() {
        $this->db_type = DB_TYPE;
        
        if ($this->db_type === 'mysql') {
            // MySQL/MySQLi configuration
            $this->host = DB_HOST;
            $this->db_name = DB_DATABASE;
            $this->username = DB_USERNAME;
            $this->password = DB_PASSWORD;
            $this->port = DB_PORT;
        } else {
            // PostgreSQL configuration (padrão para Replit)
            $this->host = $_ENV['PGHOST'] ?? 'localhost';
            $this->db_name = $_ENV['PGDATABASE'] ?? 'pdv_system';
            $this->username = $_ENV['PGUSER'] ?? 'postgres';
            $this->password = $_ENV['PGPASSWORD'] ?? 'password';
            $this->port = $_ENV['PGPORT'] ?? '5432';
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            if ($this->db_type === 'mysql') {
                // MySQL connection
                $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name, $this->port);
                
                if ($this->conn->connect_error) {
                    throw new Exception("Connection failed: " . $this->conn->connect_error);
                }
                
                $this->conn->set_charset("utf8mb4");
                
                // Retornar wrapper para compatibilidade
                return new MySQLiWrapper($this->conn);
            } else {
                // PostgreSQL connection
                $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                return $this->conn;
            }
        } catch(Exception $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("Erro de conexão com o banco de dados");
        }
    }
}

// Wrapper para MySQLi ser compatível com PDO
class MySQLiWrapper {
    private $mysqli;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }
    
    public function lastInsertId() {
        return $this->mysqli->insert_id;
    }

    public function prepare($sql) {
        // Converter sintaxe PostgreSQL para MySQL se necessário
        $sql = $this->convertSqlSyntax($sql);
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysqli->error);
        }
        return new MySQLiStatementWrapper($stmt, $this->mysqli);
    }

    public function beginTransaction() {
        return $this->mysqli->autocommit(false);
    }

    public function commit() {
        $result = $this->mysqli->commit();
        $this->mysqli->autocommit(true);
        return $result;
    }

    public function rollBack() {
        $result = $this->mysqli->rollback();
        $this->mysqli->autocommit(true);
        return $result;
    }

    private function convertSqlSyntax($sql) {
        // Converter ILIKE para LIKE (MySQL não tem ILIKE)
        $sql = str_ireplace(' ILIKE ', ' LIKE ', $sql);
        
        // Converter RETURNING para MySQL (remover, pois MySQL usa different approach)
        if (strpos($sql, 'RETURNING id') !== false) {
            $sql = str_replace(' RETURNING id', '', $sql);
        }
        
        // Converter CURRENT_DATE para MySQL
        $sql = str_replace('CURRENT_DATE', 'CURDATE()', $sql);
        
        // Converter DATE_TRUNC para MySQL - remover pois causa erro de sintaxe
        // DATE_TRUNC não tem equivalente direto no MySQL, será tratado caso a caso
        
        return $sql;
    }
}

class MySQLiStatementWrapper {
    private $stmt;
    private $result;
    private $mysqli;

    public function __construct($stmt, $mysqli) {
        $this->stmt = $stmt;
        $this->mysqli = $mysqli;
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $types = '';
            $values = [];
            
            foreach ($params as $param) {
                if (is_null($param)) {
                    $types .= 's';
                    $values[] = null;
                } elseif (is_int($param)) {
                    $types .= 'i';
                    $values[] = $param;
                } elseif (is_float($param)) {
                    $types .= 'd';
                    $values[] = $param;
                } else {
                    $types .= 's';
                    $values[] = (string)$param;
                }
            }
            
            if (!$this->stmt->bind_param($types, ...$values)) {
                throw new Exception("Bind parameters failed: " . $this->stmt->error);
            }
        }
        
        if (!$this->stmt->execute()) {
            throw new Exception("Execute failed: " . $this->stmt->error);
        }
        
        $this->result = $this->stmt->get_result();
        return true;
    }

    public function fetch() {
        if ($this->result && $this->result instanceof mysqli_result) {
            return $this->result->fetch_assoc();
        }
        
        // Para queries INSERT que retornam array com ID  
        if ($this->mysqli && $this->mysqli->insert_id > 0) {
            $id = $this->mysqli->insert_id;
            // Reset insert_id para evitar retornos duplicados
            return ['id' => $id];
        }
        
        return false;
    }

    public function fetchAll() {
        if ($this->result && $this->result instanceof mysqli_result) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function rowCount() {
        return $this->stmt->affected_rows;
    }
}
?>
