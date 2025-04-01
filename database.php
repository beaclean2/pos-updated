<?php
/**
 * Database Configuration
 * Handles database connections and operations for POS system
 */

class Database {
    private static $instance = null;
    private $connection;
    private $isTransaction = false;
    
    // Database configuration
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'pos';
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            // Create connection
            $this->connection = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->database
            );
            
            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Get singleton database connection
     * @return Database Database instance
     */
    public static function getConnection() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Prepare a SQL statement
     * @param string $sql SQL query
     * @return mysqli_stmt Prepared statement
     */
    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        $this->connection->begin_transaction();
        $this->isTransaction = true;
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        $this->connection->commit();
        $this->isTransaction = false;
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        $this->connection->rollback();
        $this->isTransaction = false;
    }
    
    /**
     * Check if in transaction
     * @return bool True if in transaction
     */
    public function inTransaction() {
        return $this->isTransaction;
    }
    
    /**
     * Get last insert ID
     * @return int Last insert ID
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Get last insert ID (alias for getLastInsertId)
     * @return int Last insert ID
     */
    public function __get($name) {
        if ($name === 'insert_id') {
            return $this->getLastInsertId();
        }
        
        throw new Exception("Undefined property: Database::$name");
    }
    
    /**
     * Execute a query and return result
     * @param string $sql SQL query
     * @return mysqli_result|bool Query result
     */
    public function query($sql) {
        return $this->connection->query($sql);
    }
    
    /**
     * Get the number of affected rows
     * @return int Number of affected rows
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->connection->close();
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>