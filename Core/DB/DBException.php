<?php namespace Xdire\Dude\Core\DB;

use Xdire\Dude\Core\App;

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
        if(App::getEnvironment() == 1) {
            parent::__construct($exceptionMessage, $exceptionCode);
        } else {
            parent::__construct($dbMessage, $exceptionCode);
        }
        $this->dbErrorCode = $dbCode;
        $this->dbErrorMessage = $dbMessage;
    }


    /**
     * @return int
     */
    protected function getDbErrorCode()
    {
        return $this->dbErrorCode;
    }

    /**
     * @return string
     */
    protected function getDbErrorMessage()
    {
        return $this->dbErrorMessage;
    }


}