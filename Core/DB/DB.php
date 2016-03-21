<?php namespace Xdire\Dude\Core\DB;

use Xdire\Dude\Core\App;

/**
 * Class DB
 * @package Xdire\Dude\Core\DB
 */
class DB {

    /** @var DBManager */
    private static $DBManager = null;

    /** @var array|null */
    private $config = null;

    /** @var \PDO */
    protected $dbinstance = null;

    /** @var int */
    private $rowsSelected = 0;

    /** @var int */
    private $rowsAffected = 0;

    /** @var bool */
    private $isTransaction;

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

    protected function resetParams() {
        $this->rowsAffected = 0;
    }
    /* -------------------------------------------------------------------------------------------------------
    |
    |   EXTENDABLE WITH DB Class
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
    |   TRANSACTIONS
    |
    |
    |
    /* ------------------------------------------------------------------------------------------------------*/
    public function transactionStart() {
        $this->dbinstance->beginTransaction();
        $this->isTransaction = true;
    }
    public function transactionCommit() {
        if($this->isTransaction) {
            $this->dbinstance->commit();
        }
        $this->isTransaction = false;
    }
    public function transactionCancel() {
        if($this->isTransaction) {
            $this->dbinstance->rollBack();
        }
        $this->isTransaction = false;
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
    protected function DBobj() {
        if($this->dbinstance) {
            return $this->dbinstance;
        } else {
            return null;
        }
    }
    /* -------------------------------------------------------------------------------------------------------
    |
    |   SELECT STATEMENT
    |
    |   $statement = valid SQL string
    |   $params = ARRAY of following types:
    |
    |       > SQL string: SELECT FROM table WHERE :variableName --> [ ':variableName', $variableName ]
    |       > SQL string: SELECT FROM table WHERE variable = ? AND variable2 = ? --> [ $variable, $variable2 ]
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * @param string $statement        SQL SELECT query
     * @param array $params            OPTIONAL Params for query
     * @param bool $compressResult     Return SplFixedArray instead of usual array
     * @return array | \SplFixedArray
     * @throws DBException
     * @throws DBNotFoundException
     */
    public function select($statement, array $params = null, $compressResult=false) {

        try {

            $query = $this->dbinstance->prepare($statement);
            $result = $query->execute($params);
            $this->rowsSelected = $query->rowCount();

            if($result) {

                if (!$compressResult) {
                    return $query->fetchAll();
                }
                else
                {
                    // Default result compressing operation
                    // using incremental raise of array size.
                    // By default it's not will double the
                    // array size, but make incremental grow
                    $return = new \SplFixedArray(2000);
                    $i = 0;
                    $k = 0;

                    while ($row = $query->fetch(\PDO::FETCH_ASSOC))
                    {
                        $return[$k] = $row;
                        $i++;
                        $k++;
                        if($i > 1999)
                        {
                            $i=0;
                            $oldIndex = $return->getSize();
                            $return->setSize($oldIndex+2000);
                        }
                    }
                    $return->setSize($k);

                    return $return;
                }

            }
            else
            {
                throw new DBNotFoundException("Data not found", 404, $query->errorInfo(), $query->errorCode());
            }
        }
        catch (\PDOException $e) {
            throw new DBReadException("Data read was failed", 500, $e->getMessage(), $e->getCode());
        }

    }
    /* -------------------------------------------------------------------------------------------------------
    |
    |   SELECT ONE ROW STATEMENT
    |
    |   $statement = valid SQL string
    |   $params = ARRAY of following types:
    |
    |       > SQL string: SELECT FROM table WHERE :variableName --> [ ':variableName', $variableName ]
    |       > SQL string: SELECT FROM table WHERE variable = ? AND variable2 = ? --> [ $variable, $variable2 ]
    |
    |   When SELECT by non-unique columns don't forget to add "LIMIT 1" to avoid overhead of database.
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * @param string $statement        SQL SELECT query
     * @param array $params            OPTIONAL Params for query
     * @return array|null              Associative array if found row, NULL otherwise
     * @throws \Xdire\Dude\Core\DB\DBException
     */
    public function selectRow($statement, array $params = null) {

        try {

            $query = $this->dbinstance->prepare($statement);
            $result = $query->execute($params);
            $this->rowsSelected = $query->rowCount();

            if($result) {
                return $query->fetch(\PDO::FETCH_ASSOC);
            } else {
                throw new DBNotFoundException("Data not found", 404, $query->errorInfo(), $query->errorCode());
            }

        }
        catch (\PDOException $e) {
            throw new DBReadException("Data read was failed", 500, $e->getMessage(), $e->getCode());
        }

    }
    /* -------------------------------------------------------------------------------------------------------
    |
    |   INSERT / UPDATE / DELETE STATEMENT
    |
    |
    |
    |
    |
    |
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function insert($statement=null,$params=null) {

        $this->rowsAffected=0;
        return $this->insertExec($statement,$params);

    }
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function update($statement=null,$params=null) {

        $this->rowsAffected=0;
        return $this->insertExec($statement,$params);

    }
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function delete($statement=null,$params=null) {

        $this->rowsAffected=0;
        $result=$this->insertExec($statement,$params);
        return ($result && $this->rowsAffected>0)? true:false;

    }

    /**
     * @param $statement
     * @param $params
     * @return bool
     * @throws \Xdire\Dude\Core\DB\DBException
     */
    private function insertExec($statement,$params) {

        try {

            $result=false;
            $query = $this->dbinstance->prepare($statement);

            if (isset($params) && isset($params[0])) {

                foreach($params as $p) {
                    $result = $query->execute($p);
                }

            } else {
                $result=$query->execute();
            }

            if($result) {

                /*
                 *  Count Rowset for multiple result after query
                 */
                if($query->nextRowset()) {

                    $rowset = 1;

                    do {
                        $rowset++;
                    } while ($query->nextRowset());

                    $this->rowsAffected = $rowset;

                } else
                    $this->rowsAffected=$query->rowCount();

                $query = null;
                return true;

            } else {
                throw new DBWriteException("Data write was failed", 500, $query->errorInfo(), $query->errorCode());
            }
        }
        catch (\PDOException $e) {

            if($e->getCode() == 23000){
                throw new DBDuplicationException("Data can't be written because of duplication", 409,
                    $e->getMessage(), $e->getCode());
            } else {
                throw new DBWriteException("Data write was failed", 500, $e->getMessage(), $e->getCode());
            }

        }

    }

    /**
     * Raw exec request.
     * Statement data need to be properly escaped.
     *
     * @param string $statement
     * @return bool
     */
    public function execute($statement) {

        $this->rowsAffected = 0;
        $result = $this->dbinstance->exec($statement);

        if($result !== false) {
            $this->rowsAffected = $result;
            return true;
        }

        return false;

    }

    /**
     * Get value of last inserted ID into the DB instance
     *
     * @return string
     */
    public function getLastInsertId() {
        return $this->dbinstance->lastInsertId();
    }

    /**
     * Get affected rows after execution of methods related to insert or execute
     *
     * @return int
     */
    public function getRowsAffected() {
        return $this->rowsAffected;
    }

    /**
     * Get rows selected by SelectBegin or Select methods
     *
     * @return int
     */
    public function getRowsSelected() {
        return $this->rowsSelected;
    }

}
