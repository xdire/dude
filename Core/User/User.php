<?php
/**
 * Created by Anton Repin.
 * Date: 2/17/16
 * Time: 1:38 PM
 */

namespace Core\User;

use Core\Face\UserPermissions;

class User
{
    /** @var int|null  */
    protected $id = null;
    /** @var string */
    protected $loginName="";
    /** @var string */
    protected $email="";

    /** @var string|null  */
    protected $firstName = null;
    /** @var string|null  */
    protected $lastName = null;
    /** @var int|null  */
    protected $vendorId = null;
    /** @var string|null  */
    protected $key = null;
    /** @var int */
    protected $level = 0;
    /** @var bool */
    protected $authorized = false;

    /** @var int */
    protected $active=0;

    /** @var UserPermissions */
    protected $security=null;
    /** @var bool  */
    protected $hasSecurity = false;

    /**
     * @return bool
     */
    public function isEmpty() {
        return (!isset($this->id)) ? true : false;
    }

    /**
     * @return array
     */
    public function toArray() {
        return ["id"=>$this->id,"login"=>$this->loginName,"email"=>$this->email,"firstName"=>$this->firstName,"lastName"=>$this->lastName,"level"=>$this->level,"active"=>$this->active==0?false:true];
    }

    /**
     * @return string
     */
    public function toJson() {
        return json_encode($this->toArray());
    }

    /**
     * @return boolean
     */
    public function hasSecurityParameters()
    {
        return $this->hasSecurity;
    }

    /**
     * Variable user permissions implementation through UserPermissions interface
     *
     * @param UserPermissions $entity
     */
    public function setSecurityPermissions(UserPermissions $entity) {
        $this->security = $entity;
        $this->hasSecurity = true;
    }

    /**
     * Return object which implementing UserPermissions
     *
     * @return UserPermissions
     */
    public function getSecurityPermissions() {
        return $this->security;
    }

    /**
     * User constructor.
     */
    function __construct() {

    }

    /**
     * @param $id
     */
    function setId($id) {
        $this->id = intval($id);
    }

    /**
     * @param string $loginName
     */
    public function setLoginName($loginName)
    {
        $this->loginName = $loginName;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param $stringName
     */
    function setFirstName($stringName) {
        $this->firstName = $stringName;
    }

    /**
     * @param $stringName
     */
    function setLastName($stringName) {
        $this->lastName = $stringName;
    }

    /**
     * @param $id
     */
    function setVendorId($id) {
        $this->vendorId = $id;
    }

    /**
     * @param $stringKey
     */
    function setKey($stringKey) {
        $this->key = $stringKey;
    }

    /**
     *  VOID
     */
    function setAuthorized() {
        $this->authorized = true;
    }

    /**
     *  VOID
     */
    function setUnAuthorized() {
        $this->authorized = false;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return null|string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getLoginName()
    {
        return $this->loginName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return int|null
     */
    public function getVendorId()
    {
        return $this->vendorId;
    }

    /**
     * @return null|string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel($level)
    {
        $this->level = (int)$level;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active == 0  ? false : true;
    }

    /**
     * @param int $active
     */
    public function setActive($active)
    {
        $this->active = (int)$active;
    }

    /**
     * @return boolean
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

}