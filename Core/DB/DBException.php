<?php namespace Xdire\Dude\Core\DB;

class DBException extends \Exception
{
    /** @var int */
    private $dbErrorCode;
    /** @var string */
    private $dbErrorMessage;

    /**
     * DBException constructor.
     * @param string        $exceptionMessage
     * @param int           $exceptionCode
     * @param string        $dbMessage
     * @param int           $dbCode
     */
    public function __construct($exceptionMessage,$exceptionCode,$dbMessage = null,$dbCode = null)
    {
        parent::__construct($exceptionMessage,$exceptionCode);
        $this->dbErrorCode = $dbCode;
        $this->dbErrorMessage = $dbMessage;
    }

    /**
     * @return int
     */
    public function getDbErrorCode()
    {
        return $this->dbErrorCode;
    }

    /**
     * @return string
     */
    public function getDbErrorMessage()
    {
        return $this->dbErrorMessage;
    }

}