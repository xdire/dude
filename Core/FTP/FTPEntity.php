<?php namespace Xdire\Dude\Core\FTP;
/**
 * Created by Anton Repin.
 * Date: 18.08.15
 * Time: 11:19
 */

/**
 * Class FTPEntity
 * @package Xdire\Dude\Core\Model
 */
class FTPEntity
{
    /** @var bool  */
    public $status;
    /** @var string  */
    public $result;
    /** @var array  */
    public $filesDownloaded;
    /** @var int  */
    public $filesSuccess;
    /** @var   */
    public $filesNotDownloaded;
    /** @var int  */
    public $filesFail;

    /** @var int  */
    public $error;
    /** @var   */
    public $errorMessage;
    /** @var int  */
    public $errorCount;
    /** @var array  */
    public $errorList;

    /**
     * @param void
     */
    function __construct(){
        $this->filesFailed = [];
        $this->filesDownloaded = [];
        $this->filesSuccess = 0;
        $this->filesFail = 0;
        $this->status = false;
        $this->result = '';

        $this->error = 0;
        $this->errorMessage = '';
        $this->errorCount = 0;
        $this->errorList = [];
    }

    /**
     * @param void
     */
    function setStatusToOk(){
        $this->status = true;
    }

    /**
     * @param void
     */
    function setStatusToFailed(){
        $this->status = false;
    }

    /**
     * @param string $fileName
     */
    function setFailedFile($fileName){
        $this->filesNotDownloaded[] = $fileName;
        $this->filesFail++;
    }

    /**
     * @param string $fileName
     */
    function setSuccessFile($fileName){
        $this->filesDownloaded[] = $fileName;
        $this->filesSuccess++;
        $this->status = true;
    }

    /**
     * @param int $num
     * @param string $msg
     */
    function setError($num,$msg){
        $this->error = $num;
        $this->errorMessage = $msg;
    }

    /**
     * @param int $num
     * @param string $msg
     */
    function addError($num,$msg){
        $this->errorCount++;
        $error = new \stdClass();
        $error->error = $num;
        $error->message = $msg;
        $this->errorList[] = $error;
    }

}