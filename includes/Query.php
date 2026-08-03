<?php
/**
 * FuelDeskPro - PDO Query Wrapper Class
 * 
 * Custom database query wrapper that enforces the project's
 * database access rules. Uses PDO with prepared statements.
 * 
 * @package FuelDeskPro
 * @author  FuelDeskPro Dev Team
 */

class Query
{
    private $pdo;
    private $stmt;
    private static $instance = null;

    /**
     * Constructor - Initialize PDO connection
     * 
     * @param string $host     Database host
     * @param string $dbname   Database name
     * @param string $username Database username
     * @param string $password Database password
     * @param string $charset  Character set (default: utf8mb4)
     */
    public function __construct($host = DB_HOST, $dbname = DB_NAME, $username = DB_USER, $password = DB_PASS, $charset = 'utf8mb4')
    {
        $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Get singleton instance
     * 
     * @return Query
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Execute SELECT query and return result set as object array
     * 
     * @param string $sql     SQL query with placeholders
     * @param array  $params  Optional parameter values for prepared statement
     * @return array          Array of objects (PDO::FETCH_OBJ)
     * 
     * Usage:
     *   $data = $objQuery->index("SELECT * FROM mst_shift");
     *   $data = $objQuery->index("SELECT * FROM mst_shift WHERE ShiftID = ?", [1]);
     */
    public function index($sql, $params = [])
    {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return $this->stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            die("Query Error: " . $e->getMessage() . "<br>SQL: " . $sql);
        }
    }

    /**
     * Execute INSERT, UPDATE, or DELETE query
     * 
     * @param string $sql     SQL query with placeholders
     * @param array  $params  Optional parameter values for prepared statement
     * @return int            Number of affected rows
     * 
     * Usage:
     *   $objQuery->inUpDel("INSERT INTO mst_shift(ShiftName) VALUES('Morning')");
     *   $objQuery->inUpDel("UPDATE mst_shift SET ShiftName = ? WHERE ShiftID = ?", ['Evening', 1]);
     */
    public function inUpDel($sql, $params = [])
    {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $this->stmt->execute($params);
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            die("Query Error: " . $e->getMessage() . "<br>SQL: " . $sql);
        }
    }

    /**
     * Get the last inserted auto-increment ID
     * 
     * @return string Last insert ID
     * 
     * Usage:
     *   $lastId = $objQuery->getLastInsertId();
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin a database transaction
     * 
     * Usage:
     *   $objQuery->begin();
     */
    public function begin()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction
     * 
     * Usage:
     *   $objQuery->commit();
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * Roll back the current transaction
     * 
     * Usage:
     *   $objQuery->rollback();
     */
    public function rollback()
    {
        $this->pdo->rollBack();
    }

    /**
     * Get the underlying PDO instance (for advanced use)
     * 
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }
}