<?php namespace Xdire\Dude\Core\DB;

use Xdire\Dude\Core\App;
use Xdire\Dude\Core\Log\Log;

/**
 * Class DB
 * @package Xdire\Dude\Core\DB
 */
class DB {

    /** @var DBException  */
    static $DBExceptionDBConnFailed = null;
    /** @var DBException  */
    static $DBExceptionNotFound = null;
    /** @var DBException  */
    static $DBExceptionReadFailed = null;
    /** @var DBException  */
    static $DBExceptionWriteFailed = null;

    /** @var array|null */
    private $config = null;
    /** @var array|null */
    private $options = null;

    /** @var \PDO */
    protected $dbinstance = null;

    /** @var int|null  */
    public $lastInsertId = null;
    /** @var bool */
    public $error = false;
    /** @var string|null */
    public $errorCode = 0;
    /** @var string|null */
    public $errorInfo = null;
    /** @var string|null */
    public $errorFullInfo = null;
    /** @var int */
    public $rowsAffected = 0;

    /** @var bool */
    private $isTransaction;
    /* -------------------------------------------------------------------------------------------------------
    |
    |   DB CLASS CONSTRUCTOR AND SERVICE FUNCTIONS
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * DB constructor.
     * @param null $conf_instance
     *
     * - Put there Database instance from config file, for example: "mysql_connection"
     *
     * @param null $options
     *
     * @throws \Exception
     */
    function __construct($conf_instance=null) {

        if(!isset($conf_instance)) {

            # Apply parameters from config
            $this->config = App::getConfig('mysql_connection');
            if(!empty($this->config))
                $this->constructDBOBJ();
            else
                throw new \DBException("Database driver can't be instantiated. Failed to load configuration.");

        } else {

            # Apply parameters from custom config instance
            $conf_cred = App::getConfig($conf_instance);
            if(isset($conf_cred)){
                $this->config = $conf_cred;
                $this->constructDBOBJ();
            }

        }

        $this->isTransaction = false;

        if(!isset(self::$DBExceptionNotFound)) {
            self::$DBExceptionNotFound = new DBException("Data not found", 404);
            self::$DBExceptionWriteFailed = new DBException("Data write was failed", 500);
            self::$DBExceptionReadFailed = new DBException("Data read was failed", 500);
            self::$DBExceptionDBConnFailed = new DBException("Database connection was failed", 500);
        }

    }

    /**
     * @throws DBException
     */
    private function constructDBOBJ() {

        if($this->config['type']=='mysql'){

            $port='';
            if(strlen($this->config['port']))

                $port='port='.$this->config['port'].';';

            try {

                $this->dbinstance = new \PDO(
                    'mysql:host=' . $this->config['host'] . ';' .
                    $port . 'dbname=' . $this->config['instance'],
                    $this->config['user'],
                    $this->config['password']);

                $this->dbinstance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // Disabled persistence connections attribute
                // need to be reused for remote servers with
                // using register_shutdown_function()
                //
                //$this->dbinstance->setAttribute(\PDO::ATTR_PERSISTENT, true);

            } catch (\PDOException $e){

                $this->setError($e->getCode(),$e->getMessage());
                Log::append('Database connection failed: '.$e->getMessage());
                throw self::$DBExceptionDBConnFailed;

            }

        }
    }

    protected function resetError() {
        $this->error = false;
        $this->errorCode = 0;
        $this->errorInfo = "";
    }

    protected function setError($code,$message) {
        $this->error = true;
        $this->errorCode = $code;
        $this->errorInfo = $message;
    }

    protected function resetParams() {
        $this->lastInsertId = null;
        $this->rowsAffected = 0;
    }
    /* -------------------------------------------------------------------------------------------------------
    |
    |   EXTENDABLE
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
     * @throws DBException
     */
    protected function selectBegin($statement) {

        $this->resetError();

        try {

            $query = $this->dbinstance->prepare($statement);
            $result = null;

            $result = $query->execute();
            if($result) {
                return $query;
            } else {
                $this->setError($query->errorCode(),$query->errorInfo());
                throw self::$DBExceptionNotFound;
            }

        } catch (\PDOException $e) {

            $this->setError($e->getCode(),$e->getMessage());
            Log::append('Database query object encounter error: '.$e->getMessage());
            throw self::$DBExceptionReadFailed;

        }

    }

    /**
     * RAW Insert Query
     * RETURNS PDO Statement for next manual processing
     *
     * @param $statement
     * @return \PDOStatement
     * @throws DBException
     */
    protected function writeBegin($statement) {

        $this->resetError();
        $this->resetParams();
        try {

            $query = $this->dbinstance->prepare($statement);
            $result = $query->execute();
            if ($result) {
                $this->lastInsertId=$this->dbinstance->lastInsertId();
                $this->rowsAffected=$query->rowCount();
                return $query;
            }
            else {
                $this->setError($query->errorCode(),$query->errorInfo());
                throw self::$DBExceptionWriteFailed;
            }

        } catch (\PDOException $e) {

            Log::append('Database query object encounter error: ' . $e->getMessage() . ' Query string: ' . $statement);
            $this->setError($e->getCode(),$e->getMessage());

            if($e->getCode() == 23000) {
                throw new DBException("Data can't be written because of duplication",409);
            } else {
                throw self::$DBExceptionWriteFailed;
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
     * @param null $statement
     * @param null $params
     * @param bool $compressResult
     * @return array|\SplFixedArray
     * @throws DBException
     */
    public function select($statement,$params=null,$compressResult=false) {
        $this->resetError();

        try {

            $query = $this->dbinstance->prepare($statement);
            $result=null;

            if (isset($params) && isset($params[0])) {

                foreach ($params as $p) {
                    $result = $query->execute($p);
                }

            } else {
                $result=$query->execute();
            }

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
                    $fa = \PDO::FETCH_ASSOC;
                    while ($row = $query->fetch($fa))
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
                $this->setError($query->errorCode(),$query->errorInfo());
                throw self::$DBExceptionNotFound;
            }
        }
        catch (\PDOException $e){
            $this->setError($e->getCode(),$e->getMessage());
            Log::append('Database query object encounter error: '.$e->getMessage());
            throw self::$DBExceptionReadFailed;
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
    /* ------------------------------------------------------------------------------------------------------*/
    /**
     * @param null $statement
     * @param null $params
     * @return mixed|null
     * @throws \Xdire\Dude\Core\DB\DBException
     */
    public function selectRow($statement=null,$params=null) {

        $this->resetError();

        try {

            $query = $this->dbinstance->prepare($statement);
            $result=null;

            if (isset($params) && isset($params[0])) {

                foreach ($params as $p) {
                    $result = $query->execute($p);
                }

            } else {
                $result=$query->execute();
            }

            if($result)
                return $query->fetch(\PDO::FETCH_ASSOC,\PDO::FETCH_ORI_LAST);
            else {
                $this->setError($query->errorCode(),$query->errorInfo());
                throw self::$DBExceptionNotFound;
            }
        }
        catch (\PDOException $e){
            $this->setError($e->getCode(),$e->getMessage());
            Log::append('Database query object encounter error: '.$e->getMessage());
            throw self::$DBExceptionReadFailed;
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

        $this->resetError();
        $this->resetParams();

        try {
            $result=false;
            $query = $this->dbinstance->prepare($statement);

            if (isset($params) && isset($params[0])) {

                foreach($params as $p){
                    $result = $query->execute($p);
                }

            } else {
                $result=$query->execute();
            }

            if($result) {

                $this->lastInsertId=$this->dbinstance->lastInsertId();
                $this->rowsAffected=$query->rowCount();

                if($query->nextRowset()) {

                    $rowset=1;

                    do {
                        $rowset++;
                    } while ($query->nextRowset());

                    $this->rowsAffected = $rowset;

                }

                $query = null;
                return true;

            } else {
                $this->setError($query->errorCode(),$query->errorInfo());
                throw self::$DBExceptionWriteFailed;
            }
        }
        catch (\PDOException $e) {

            Log::append('Database query object encounter error: '.$e->getMessage().' Query string: '.$statement);
            $this->setError($e->getCode(),$e->getMessage());

            if($e->getCode() == 23000){
                throw new DBException("Data can't be written because of duplication",409);
            } else {
                throw self::$DBExceptionWriteFailed;
            }

        }

    }

    /**
     * @return boolean
     */
    public function isError()
    {
        return $this->error;
    }

    /**
     * @return null|string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return null|string
     */
    public function getErrorInfo()
    {
        return $this->errorInfo;
    }

}