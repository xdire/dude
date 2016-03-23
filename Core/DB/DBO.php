<?php namespace Xdire\Dude\Core\DB;

use Xdire\Dude\Core\App;

/**
 * Class DBO
 * @package Xdire\Dude\Core\DB
 */
abstract class DBO {

    /** @var DBManager */
    private static $DBManager = null;

    /** @var array|null */
    private $config = null;

    /** @var \PDO */
    protected $dbinstance = null;

    /** @var int */
    protected $rowsSelected = 0;

    /** @var int */
    protected $rowsAffected = 0;

    /** @var bool */
    protected $isTransaction;

    /* -------------------------------------------------------------------------------------------------------
    |
    |   DB CLASS CONSTRUCTOR AND SERVICE FUNCTIONS
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * DB constructor.
     * @param string|null $configInstance - Database instance from config file, for example: "mysql_connection"
     * @param bool $useReusableConnection - Set this to true to use DBManager to hold DBConnections for reuse
     *
     * @throws \Exception
     */
    function __construct($configInstance = null, $useReusableConnection = true) {

        $this->isTransaction = false;

        if(!isset($configInstance)) {

            # Apply parameters from config
            $this->config = App::getConfig('mysql_connection');
            if(isset($this->config)) {
                $this->constructDBOBJ($useReusableConnection);
                return;
            }

        } else {

            # Apply parameters from custom config instance
            $conf_cred = App::getConfig($configInstance);
            if(isset($conf_cred)) {
                $this->config = $conf_cred;
                $this->constructDBOBJ($useReusableConnection);
                return;
            }

        }

        throw new DBException("Database driver can't be instantiated. Failed to load configuration.",
            500, "DB Connection failed", 500);

    }

    /**
     * Construct PDO Object from DBManager or from parameters
     * @param bool $useReusableConnection
     *
     * @throws DBException
     */
    private function constructDBOBJ($useReusableConnection) {

        if($this->config['type']=='mysql') {

            $port = isset($this->config['port']) ? $this->config['port'] : '';
            $host = isset($this->config['host']) ? $this->config['host'] : '';
            $sock = isset($this->config['sock']) ? $this->config['sock'] : '';
            $inst = isset($this->config['instance']) ? $this->config['instance'] : '';

            // Prepare standard variables
            $port = 'port=' . $port . ';';
            $host = 'mysql:host=' . $host . ';';

            // If instance will use unix-socket file
            if (strlen($sock) > 0) {
                $host = 'mysql:unix_socket=' . $this->config['sock'] . ';';
            }

            if($useReusableConnection) {

                if(!isset(self::$DBManager)) {
                    self::$DBManager = new DBManager();
                }

                if($i = self::$DBManager->getDbInstance($host,$port,$inst)) {
                    $this->dbinstance = &$i;
                    return;
                }

                $this->_constructDBOBJ($host,$port,$inst,$this->config['user'],$this->config['password']);

                self::$DBManager->addInstance($host,$port,$inst,$this->dbinstance);

            } else {

                $this->_constructDBOBJ($host,$port,$inst,$this->config['user'],$this->config['password']);

            }

        }

    }
    /**
     * Create PDO Object from parameters
     *
     * @param string $host
     * @param string $port
     * @param string $instance
     * @param string $user
     * @param string $pwd
     * @throws DBException
     */
    private function _constructDBOBJ($host,$port,$instance,$user,$pwd) {

        try {

            $this->dbinstance = new \PDO(
                $host .
                $port . 'dbname=' . $instance,
                $user,
                $pwd);

            $this->dbinstance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        } catch (\PDOException $e){
            throw new DBException("Database connection was failed", 500, $e->getMessage(), $e->getCode());
        }

    }

    /* -------------------------------------------------------------------------------------------------------
    |
    |   EXTENDABLE WITH DBO Class
    |
    |
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * RAW Select Query
     * RETURNS PDO Statement for manual result parsing by cursor
     *
     * @param $statement
     * @return \PDOStatement
     * @throws DBReadException
     * @throws DBNotFoundException
     */
    protected function selectBegin($statement) {

        try {

            $query = $this->dbinstance->prepare($statement);

            $result = $query->execute();
            $this->rowsSelected = $query->rowCount();

            if($result) {

                return $query;

            } else {

                throw new DBNotFoundException("Data not found", 404, $query->errorInfo(), $query->errorCode());

            }

        } catch (\PDOException $e) {

            throw new DBReadException("Data read was failed", 500, $e->getMessage(), $e->getCode());

        }

    }

    /**
     * RAW Insert Query
     * RETURNS PDO Statement for next manual processing
     *
     * @param $statement
     * @return \PDOStatement
     * @throws DBWriteException
     * @throws DBDuplicationException
     */
    protected function writeBegin($statement) {

        $this->rowsAffected = 0;

        try {

            $query = $this->dbinstance->prepare($statement);
            $result = $query->execute();
            if ($result) {
                $this->rowsAffected=$query->rowCount();
                return $query;
            }
            else {
                throw new DBWriteException("Data write was failed", 500, $query->errorInfo(), $query->errorCode());
            }

        } catch (\PDOException $e) {

            if($e->getCode() == 23000) {
                throw new DBDuplicationException("Data can't be written because of duplication", 409,
                    $e->getMessage(),$e->getCode());
            } else {
                throw new DBWriteException("Data write was failed", 500, $e->getMessage(), $e->getCode());
            }

        }

    }

    /**
     * Fetch Wrapper
     *
     * using fetch(PDO::FETCH_ASSOC)
     *
     * @param \PDOStatement $q
     * @return mixed|null
     */
    protected function fetchRow(\PDOStatement $q) {
        if($row = $q->fetch(2)) return $row;
        return null;
    }

    /* -------------------------------------------------------------------------------------------------------
    |
    |   DBH OBJECT
    |
    |
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * DB Object as PDO instance
     * -------------------------
     * @return null|\PDO
     */
    protected function getDBObject() {
        return $this->dbinstance;
    }
    /**
     * Get value of last inserted ID into the DB instance
     *
     * @return string
     */
    protected function getLastInsertId() {
        return $this->dbinstance->lastInsertId();
    }

    /**
     * Get affected rows after execution of methods related to insert or execute
     *
     * @return int
     */
    protected function getRowsAffected() {
        return $this->rowsAffected;
    }

    /**
     * Get rows selected by SelectBegin or Select methods
     *
     * @return int
     */
    protected function getRowsSelected() {
        return $this->rowsSelected;
    }

}
