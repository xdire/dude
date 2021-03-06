<?php namespace Xdire\Dude\Core\FTP;

class FTP {

    /** @var string */
    public $server;
    /** @var string */
    public $serverUser;
    /** @var string */
    public $serverPassword;
    /** @var string */
    public $serverMethod;

    public $resultFile = false;

    /** @var null | resource */
    protected $connection = null;
    /** @var bool */
    protected $ftpUseSSL = true;
    /** @var array */
    protected $files = array();

    private $errorObject = null;
    /** @var int */
    public $error;
    /** @var string */
    public $errorMessage;

    private $localRootFolder=null;

    private $methods = array(
        0=>'ASCII',
        1=>'BINARY'
    );

    function __construct(Array $params) {

        if(isset($params['server'])){

            foreach($params as $k=>$v){

                switch($k){

                    case 'server':
                        $this->server = $v;
                        break;
                    case 'user':
                        $this->serverUser = $v;
                        break;
                    case 'pass':
                        $this->serverPassword = $v;
                        break;
                    case 'method':
                        if(isset($this->methods[$v])){
                            $this->serverMethod = $this->methods[$v];
                        } else
                            $this->serverMethod = $v;
                        break;
                    case 'local':
                        if(is_array($v)){
                            $count=0;
                            foreach($v as $fname){
                                $this->files[$count]['local'] = $fname;
                                $count++;
                            }
                        } else {
                            $this->files[0]['local'] = $v;
                        }
                        break;
                    case 'remote':
                        if(is_array($v)){
                            $count=0;
                            foreach($v as $fname){
                                $this->files[$count]['remote'] = $fname;
                                $count++;
                            }
                        } else {
                            $this->files[0]['remote'] = $v;
                        }
                        break;

                    default: break;

                }

            }

        }

    }

    /**
     * @param string $localRootFolder
     */
    public function setLocalRootFolder($localRootFolder)
    {
        $this->localRootFolder = $localRootFolder;
    }

    public function seeFilesList(){
        return $this->files;
    }
    /**
     * @param boolean $bool
     */
    public function useSSL($bool){
        if($bool === true || $bool === false)
            $this->ftpUseSSL = $bool;
    }

    public function putFiles(){

        if($this->connection === null) {
            $this->createFTPConn();
        }

        foreach($this->files as $file){

            if(!$this->putFile($file,$this->serverMethod)){
                throw new \Exception('Cannot put file',0);
            }

        }

        $this->closeConnection();

    }

    public function getFiles(){

        $result = new FTPEntity();

        if($this->connection === null) {
            $this->createFTPConn();
        }

        foreach($this->files as $file){

            if(!$this->getFile($file,$this->serverMethod)){
                $result->setFailedFile($file['remote']);
                //throw new \Exception('Cannot get file',0);
            } else {
                $result->setSuccessFile($file['local']);
            }

        }

        $this->closeConnection();
        return $result;

    }

    public function getFilesUsingWeakDependency($directory=null){

        if($this->connection === null) {
            $this->createFTPConn();
        }

        $result = new FTPEntity();

        foreach($this->files as $file){

            if(isset($directory) && isset($file['remote'])) {
                $fileRemote = ltrim($file['remote'], '/');
                $fixedDir = rtrim($directory, '/');
                $file['remote'] = $fixedDir.'/'.$fileRemote;
            }

            if(!$this->getFile($file,$this->serverMethod)){

                if(isset($file['remote'])) {

                    $result->setFailedFile($file['remote']);

                }

            } else {

                if(isset($file['local'])) {
                    $result->setSuccessFile($file['local']);
                }

            }

        }

        $this->closeConnection();
        return $result;

    }

    public function getUnattachedFilesUsingWeakDependency($directoryRemote=null,$directoryLocal=null){

        if($this->connection === null) {
            $this->createFTPConn();
        }

        $result = new FTPEntity();

        foreach($this->files as $file){

            if(isset($directoryRemote) && isset($file['remote'])) {
                $fileRemote = ltrim($file['remote'], '/');
                $fixedDir = rtrim($directoryRemote, '/');
                $file['remote'] = $fixedDir.'/'.$fileRemote;
            }

            if(!isset($file['local'])){
                $re = explode('/',$file['remote']);
                $rl = $re[count($re)-1];
                $fixedDir = rtrim($directoryLocal, '/');
                $file['local'] = $fixedDir.'/'.$rl;
            }

            if(!$this->getFile($file,$this->serverMethod)){

                if(isset($file['remote'])) {

                    $result->setFailedFile($file['remote']);

                }

            } else {

                if(isset($file['local'])) {
                    $result->setSuccessFile($file['local']);
                }

            }

        }

        $this->closeConnection();
        return $result;

    }

    /**
     * @param               $directory
     * @param               $fileDescription
     * @param   bool        $dontCloseConnection
     * @throws  FTPException
     */
    public function scanDirectoryForFilesLike($directory,$fileDescription,$dontCloseConnection = false){

        if($this->connection === null){
            if(!$this->createFTPConn()){
                throw new FTPException('Cannot establish connection',500);
            }
        }

        $keywords = array();
        if(strpos($fileDescription,'&&') > -1) {
            $joinScan = explode('&&', $fileDescription);
        } else {
            $joinScan = [];
        }
        if(strpos($fileDescription,'||') > -1) {
            $orScan = explode('||',$fileDescription);
        } else {
            $orScan = [];
        }

        $joins = $this->fulfillKeywords($keywords,$joinScan,0);
        $orstm = $this->fulfillKeywords($keywords,$orScan,1);

        $directory = ($directory == "" || $directory == " ") ? '.' : $directory;
        if($directory != '.') {
            if ($directory[0] !== "/") {
                $directory = "/" . $directory;
            }
        }

        $listOfFiles = ftp_nlist($this->connection,$directory);

        /*echo '<pre>';
        var_dump($listOfFiles);
        echo '</pre>';*/

        $createdFiles = 0;

        if(is_array($listOfFiles)) {

            foreach ($listOfFiles as $file) {

                $joinHit = false;
                $orHit = false;

                if ($joins > 0) {
                    $joinHit = $this->checkForJoinsMatch($keywords, $joins, $file);
                }

                if ($orstm > 0) {
                    $orHit = $this->checkForOrMatch($keywords, $file);
                }

                if ($joinHit || $orHit) {

                    if(isset($this->files[$createdFiles])) {
                        $this->files[$createdFiles]['remote'] = $file;
                        $createdFiles++;
                    } else {
                        $this->files[++$createdFiles]['remote'] = $file;
                    }

                }

            }

        }

        if(!$dontCloseConnection){
            if($this->connection !== null){
                $this->closeConnection();
            }
        }

    }

    private function checkForJoinsMatch(&$kwarray,$joins,$word){

        $count = 0;

        foreach($kwarray as $check => $type)
        {
            $checkthis = (string)$check;
            if($type == 0) {

                if (strpos($word,$checkthis) !== false) {
                    $count++;
                }

            }
        }

        if($count == $joins){
            return true;
        } else {
            return false;
        }

    }

    private function checkForOrMatch(&$kwarray,$word){

        $count = 0;

        foreach($kwarray as $check=>$type){

            $checkthis = (string)$check;
            if($type == 1) {
                if (strpos($word, $checkthis) !== false) {
                    $count++;
                }
            }

        }

        if($count > 0){
            return true;
        } else {
            return false;
        }

    }

    private function fulfillKeywords(&$kwarray,&$words,$type){

        $count=0;
        if(count($words) >0) {
            foreach ($words as $w) {
                $word = (string)trim($w);
                if (!isset($kwarray[$word])) {
                    $kwarray[$word] = $type;
                    $count++;
                }
            }
        }
        return $count;

    }

    /**
     * @return bool
     * @throws FTPException
     */
    protected function createFTPConn(){

        if($this->ftpUseSSL){
            $conn = ftp_ssl_connect($this->server);
        } else {
            $conn = ftp_connect($this->server);
        }

        if($conn !== false) {

            $go = false;
            if(@ftp_login($conn, $this->serverUser, $this->serverPassword)) {
                $go = true;
            } else {

                ftp_close($conn);
                if(($conn = ftp_connect($this->server)) !== false) {
                    if(@ftp_login($conn, $this->serverUser, $this->serverPassword)) {
                        $go = true;
                    } else {

                        $this->errorObject = error_get_last();
                        $this->parseFtpError();
                        throw new FTPException("FTP Driver cannot connect to: "
                            .$this->server." with this login: ".$this->serverUser
                            .". Error: ".$this->error.", Message: ".$this->errorMessage,401);
                    }
                } else {

                    $this->errorObject = error_get_last();
                    $this->parseFtpError();
                    throw new FTPException("FTP Driver cannot connect to: "
                        .$this->server." with this login: ".$this->serverUser
                        .". Error: ".$this->error.", Message: ".$this->errorMessage,401);
                }

            }

            if($go){

                ftp_pasv($conn, true);
                ftp_set_option($conn, FTP_TIMEOUT_SEC, 1200);

                $this->connection = $conn;
                set_time_limit(0);
                return true;
            }

        } else {

            throw new FTPException("FTP Driver cannot connect to: "
                .$this->server." with this login: ".$this->serverUser
                .". Error: 500, Message: Connection problems",500);

        }

        return false;
    }

    /**
     * @param     $file
     * @param int $mode
     * @return bool
     * @throws FTPException
     */
    protected function getFile($file,$mode = 0){

        if($this->connection !== null){

            if(isset($file['remote']) && isset($file['local'])){

                switch ($mode) {
                    case 0:
                        $transferMode = FTP_ASCII;
                        break;
                    case 1:
                        $transferMode = FTP_BINARY;
                        break;
                    default:
                        $transferMode = FTP_ASCII;
                        break;
                }

                if(isset($this->localRootFolder))
                    $localFile = $this->localRootFolder . '/' . $file['local'];
                else
                    $localFile = $file['local'];

                if(strlen($file['remote']) > 0) {

                    if (ftp_get($this->connection, $localFile, $file['remote'], $transferMode)) {
                        return true;
                    } else {
                        throw new FTPException('Failed to execute getFile function : '.$this->server, 500);
                    }

                } else {

                    throw new FTPException('Failed to use getFile function, remote File is not defined in scheme: '.$this->server,404);

                }

            }

        } else {

            throw new FTPException('Failed to use getFile function, connection is not available: '.$this->server,500);

        }

        return false;
    }

    /**
     * @param   string      $file
     * @param   int         $mode
     * @return  bool
     * @throws  FTPException
     */
    protected function putFile($file,$mode = 0){

        if($this->connection !== null){

            if(isset($file['remote'])) {

                switch ($mode) {
                    case 0:
                        $transferMode = FTP_ASCII;
                        break;
                    case 1:
                        $transferMode = FTP_BINARY;
                        break;
                    default:
                        $transferMode = FTP_ASCII;
                        break;
                }

                if(isset($this->localRootFolder))
                    $localFile = $this->localRootFolder . '/' . $file['local'];
                else
                    $localFile = $file['local'];

                if (ftp_put($this->connection, $file['remote'], $localFile, $transferMode)) {
                    return true;
                } else {
                    throw new FTPException('Failed to execute putFile function : '.$this->server, 500);
                }

            }

        } else {

            throw new FTPException('Failed to use getFile function, connection is not available: '.$this->server,500);

        }

        return false;
    }

    /**
     * @param $file
     * @param $folder
     * @return null|string
     * @throws FTPException
     */
    public function unZipFile($file,$folder){

        if(isset($this->localRootFolder))
            $localPath = $this->localRootFolder . '/' . $file;
        else
            $localPath = $file;

        if(file_exists($localPath)){

            $zip = new \ZipArchive;
            $unzip = $zip->open($localPath);
            $name = '';

            if ($unzip) {
                $name = $zip->getNameIndex(0);
                $zip->extractTo($folder.'/');
                $zip->close();
            }
            else
            {
                throw new FTPException('Failed to use unzip file: '.$file,500);
            }

            $oldName = explode(".",$file);
            $newName = $oldName[0].'.txt';

            if(isset($this->localRootFolder)) {
                rename($this->localRootFolder.'/'.$folder . '/' . $name, $this->localRootFolder.'/'.$folder . '/' . $newName);
                unlink($this->localRootFolder.'/'.$folder . '/' . $file);
            } else {
                rename($folder . '/' . $name, $folder . '/' . $newName);
                unlink($folder . '/' . $file);
            }

            return $newName;

        }

        return null;

    }

    protected function closeConnection(){
        if(isset($this->connection)) {
            if(ftp_close($this->connection))
                $this->connection = null;
        }
    }

    private function parseFtpError() {

        if(isset($this->errorObject['type'])){
            $this->error = $this->errorObject['type'];
        }
        if(isset($this->errorObject['message'])){
            $this->errorMessage = $this->errorObject['message'];
        }

    }

}