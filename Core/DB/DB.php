<?php namespace Xdire\Dude\Core\DB;

class DB extends DBO
{
    function __construct($configInstance=null, $useReusableConnection=true)
    {
        parent::__construct($configInstance, $useReusableConnection);
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

        return $this->insertExec($statement,$params);

    }
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function update($statement=null,$params=null) {

        return $this->insertExec($statement,$params);

    }
    /**
     * @param null $statement
     * @param null $params
     * @return bool
     */
    public function delete($statement=null,$params=null) {

        $result=$this->insertExec($statement,$params);
        return ($result && $this->rowsAffected>0) ? true:false;

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
     */
    public function select($statement, array $params = null, $compressResult = false) {

        try {

            $query = $this->dbinstance->prepare($statement);
            $result = $query->execute($params);
            $this->rowsSelected = $query->rowCount();

            if($result) {

                if (!$compressResult) {
                    return $query->fetchAll(\PDO::FETCH_ASSOC);
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
                            $i = 0;
                            $oldIndex = $return->getSize();
                            $return->setSize($oldIndex + 2000);
                        }
                    }
                    $return->setSize($k);

                    return $return;
                }

            }
            else
                throw new DBReadException("Data read was failed", 404, $query->errorInfo(), $query->errorCode());

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
     * @throws DBReadException
     */
    public function selectRow($statement, array $params = null) {

        try {

            $query = $this->dbinstance->prepare($statement);
            $result = $query->execute($params);
            $this->rowsSelected = $query->rowCount();

            if($result) {
                return $query->fetch(\PDO::FETCH_ASSOC);
            } else
                throw new DBReadException("Data read was failed", 404, $query->errorInfo(), $query->errorCode());

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
     * @param $statement
     * @param $params
     * @return bool
     * @throws DBException
     */
    private function insertExec($statement,$params) {

        try {

            $this->rowsAffected = 0;
            $result = false;
            $query = $this->dbinstance->prepare($statement);

            if (isset($params) && isset($params[0])) {

                foreach($params as $p) {
                    $result = $query->execute($p);
                }

            } else {
                $result = $query->execute();
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
                    $this->rowsAffected = $query->rowCount();

                $query = null;
                return true;

            } else
                throw new DBWriteException("Data write was failed", 500, $query->errorInfo(), $query->errorCode());

        }
        catch (\PDOException $e) {

            if($e->getCode() == 23000) {

                throw new DBDuplicationException("Data can't be written because of duplication", 409,
                    $e->getMessage(), $this->dbinstance->errorCode());

            } else {
                throw new DBWriteException("Data write was failed", 500, $e->getMessage(), $this->dbinstance->errorCode());
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
    |   INFORMATION
    |
    |
    |
    /* ------------------------------------------------------------------------------------------------------*/
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
