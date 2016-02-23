<?php
/**
 * Created by Anton Repin.
 * Date: 2/15/16
 * Time: 11:50 AM
 */

namespace Core\Server;

use Core\User\User;

class Request
{

    private $remoteHost = null;

    private $path = null;

    private $pathLast = null;

    private $headers = [];

    private $parameters = [];

    private $queryParameters = [];

    private $postData = null;

    private $authKey = null;

    private $authUser = null;
    /** @var int|null */
    private $apiVer = null;
    /** @var User|null */
    private $user = null;
    /** @var bool */
    private $authorized = false;
    /** @var bool */
    private $legit = false;

    function __construct() {
        $this->_setup();
        if(isset($this->headers) && isset($this->remoteHost))
            $this->legit = true;
    }

    private function _setup() {

        if($this->headers = getallheaders()) {
            $a = [];
            foreach($this->headers as $name=>$value) {
                if($name == "x-request-sig") {
                    $this->authKey = $value;
                } elseif ($name == "User-Agent-Id") {
                    $this->authUser = $value;
                } elseif ($name == "User-Agent-Ver") {
                    $this->apiVer = $value;
                }
                $a[strtolower($name)] = $value;
            }
            $this->headers = $a;
        }

        if(isset($_POST)) {
            $this->postData = $_POST;
        }

        if(isset($_SERVER['REMOTE_ADDR'])) {
            $this->remoteHost = $_SERVER['REMOTE_ADDR'];
        }

    }

    public function getHeader($header) {
        $l = strtolower($header);
        if(isset($this->headers[$l])) {
            return $this->headers[$l];
        }
        return null;
    }

    public function getPathParameter($parameter){
        if(isset($this->parameters[$parameter])) {
            return $this->parameters[$parameter];
        }
        return null;
    }

    public function getQueryParameter($parameter){
        if(isset($this->queryParameters[$parameter])) {
            return $this->queryParameters[$parameter];
        }
        return null;
    }

    public function getPostParameter($parameter){
        if(isset($this->postData[$parameter])) {
            return $this->postData[$parameter];
        }
        return null;
    }

    public function __setPathLastElement($string) {
        $this->pathLast = $string;
    }

    public function __setPath($string) {
        $this->path = $string;
    }

    public function __setQueryParameters($queryParameters){
        $this->queryParameters = $queryParameters;
    }

    public function __setPathParameter($name,$value){
        $this->parameters[$name]=$value;
    }
    public function __setPathParameters($pathParameters){
        $this->parameters = $pathParameters;
    }

    /**
     * @param User $userObject
     */
    public function __setUser(User $userObject) {
        $this->user = $userObject;
        if($userObject->isAuthorized()){
            $this->authorized = true;
        }
    }

    /**
     * VOID
     */
    public function __setAuthorized()
    {
        $this->authorized = true;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return null
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * @return null
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return null
     */
    public function getPathLast()
    {
        return $this->pathLast;
    }

    /**
     * @return null
     */
    public function getPostData()
    {
        return $this->postData;
    }

    /**
     * @return null
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @return null
     */
    public function getAuthUser()
    {
        return $this->authUser;
    }

    /**
     * @return null
     */
    public function getApiVer()
    {
        return $this->apiVer;
    }

    /**
     * @return boolean
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

    /**
     * @return boolean
     */
    public function isLegit()
    {
        return $this->legit;
    }


}