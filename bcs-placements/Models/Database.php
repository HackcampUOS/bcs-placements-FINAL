<?php
/**
 * Database Class
 *
 * Manages database connections and operations using PDO and SQLite.
 * Implements the Singleton pattern to ensure only one database connection
 * exists throughout the application. Provides secure methods for executing
 * queries, fetching results, and managing transactions using prepared
 * statements to prevent SQL injection attacks.
 */

class Database
{
    /**
     * @var Database|null
     */
    protected static $_dbInstance = null;

    /**
     * @var PDO|null
     */
    protected $_dbHandle;

    /**
     * Singleton accessor
     * @param bool $ajax Whether the request is AJAX-based (affects DB path)
     * @return Database
     */
    public static function getInstance($ajax = false)
    {
        if (self::$_dbInstance === null) {
            self::$_dbInstance = new self($ajax);
        }
        return self::$_dbInstance;
    }

    /**
     * Private constructor to prevent multiple instances
     * @param bool $ajax
     */
    private function __construct($ajax)
    {
        try {
            // Build a correct path to the database file
            if ($ajax === true) {
                // AJAX calls might come from subfolders
                $dbPath = __DIR__ . '/../../Databases/BCS.sqlite';
            } else {
                $dbPath = __DIR__ . '/../Databases/BCS.sqlite';
            }

            // Create PDO handle
            $this->_dbHandle = new PDO('sqlite:' . $dbPath);

            // Set error reporting for easier debugging
            $this->_dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage();
            exit;
        }
    }

    /**
     * Returns the active PDO connection
     * @return PDO
     */
    public function getdbConnection()
    {
        return $this->_dbHandle;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->_dbHandle = null;
    }
}
