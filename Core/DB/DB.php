<?php
/**
 * Created by Anton Repin.
 * User: xdire
 * Date: 20.05.15
 * Time: 9:00
 *
 *
 */

namespace Core\DB;
use Core\App;
use Core\Log\Log;

/**
 * Class DB
 * @package Core\DB
 *
 */
class DB {

    /* -------------------------------------------------------------------------------------------------------
    |
    |   DB CLASS CONSTRUCTOR AND SERVICE FUNCTIONS
    |
    /* ------------------------------------------------------------------------------------------------------*/
    /** @var array|null */
    private $config=null;
    /** @var array|null  */
    private $options=null;

    /** @var \PDO  */
    private $dbinstance=null;

    /** @var int|null  */
    public $lastInsertId=null;
    /** @var int|null */
    public $errorCode=0;
    /** @var string|null */
    public $errorInfo=null;
    /** @var string|null */
    public $errorFullInfo=null;
    /** @var int */
    public $rowsAffected=0;

    // --------------------
    /** @var bool */
    private $isTransaction;

    function __construct($conf_instance=null,$options=null){

        if(is_array($options)){
            $this->options=$options;
        }
        if(!isset($conf_instance)){

            # Apply parameters from config
            $this->config = App::getConfig('mysql_connection');
            $this->constructDBOBJ();

        } else {

            # Apply parameters from custom config instance
            $conf_cred = App::getConfig($conf_instance);
            if(isset($conf_cred)){
                $this->config = $conf_cred;
                $this->constructDBOBJ();
            }

        }

        $this->isTransaction = false;

    }

    private function constructDBOBJ(){

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

            } catch (\PDOException $e){

                Log::append('Database connection failed: '.$e->getMessage());

            }
        }
    }

    /* -------------------------------------------------------------------------------------------------------
    |
    |   TRANSACTIONS
    |
    |
    |
    /* ------------------------------------------------------------------------------------------------------*/
    public function transactionStart(){
        $this->dbinstance->beginTransaction();
        $this->isTransaction = true;
    }
    public function transactionCommit(){
        if($this->isTransaction) {
            $this->dbinstance->commit();
        }
        $this->isTransaction = false;
    }
    public function transactionCancel(){
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
     * @return null|\PDO
     */
    public function DBobj(){
        if($this->dbinstance){
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
     * @return array|null
     */
    public function select($statement=null,$params=null,$compressResult=false){

        if($this->dbinstance && $statement){

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
                    $this->errorFullInfo=$query->errorInfo();
                }
            }
            catch (\PDOException $e){
                $this->errorInfo = $e->getMessage();
                Log::append('Database query object encounter error: '.$e->getMessage());
            }

        }

        return null;
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
     */
    public function selectRow($statement=null,$params=null){

        if($this->dbinstance && $statement){

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

                if($result) return $query->fetch(\PDO::FETCH_ASSOC,\PDO::FETCH_ORI_LAST);
                else {
                    $this->errorFullInfo=$query->errorInfo();
                }
            }
            catch (\PDOException $e){
                $this->errorInfo = $e->getMessage();
                Log::append('Database query object encounter error: '.$e->getMessage());
            }

        }

        return null;

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
    public function insert($statement=null,$params=null){
        $this->rowsAffected=0;
        return $this->insertExec($statement,$params);
    }
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function update($statement=null,$params=null){
        $this->rowsAffected=0;
        return $this->insertExec($statement,$params);
    }
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function delete($statement=null,$params=null){
        $this->rowsAffected=0;
        $result=$this->insertExec($statement,$params);
        return ($result && $this->rowsAffected>0)? true:false;

    }
    /**
     * @param $statement
     * @param $params
     * @return bool
     * @throws \Exception : Only if it in Transactional mode
     */
    private function insertExec($statement,$params){

        if($this->dbinstance && $statement){

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
                    $this->errorCode=$this->dbinstance->errorCode();
                    $this->errorFullInfo=$this->dbinstance->errorInfo();
                }
            }
            catch (\PDOException $e){

                $this->errorInfo = $e->getMessage();
                $this->errorCode=$e->getCode();
                Log::append('Database query object encounter error: '.$e->getMessage().' Query string: '.$statement);

                // Transaction handler
                if($this->isTransaction){
                    $this->isTransaction = false;
                    throw new \Exception('Database query object encounter error: '.$e->getMessage());
                }

            }

        }

        return false;
    }



}