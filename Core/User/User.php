<?php
/**
 * Created by Anton Repin.
 * Date: 2/17/16
 * Time: 1:38 PM
 */

namespace Core\User;

class User
{
    /** @var int|null  */
    private $id = null;
    /** @var string|null  */
    private $name = null;
    /** @var string|null  */
    private $firstName = null;
    /** @var string|null  */
    private $lastName = null;
    /** @var int|null  */
    private $vendorId = null;
    /** @var string|null  */
    private $key = null;
    /** @var int */
    private $level = 0;
    /** @var bool */
    private $authorized = false;

    /**
     * User constructor.
     */
    function __construct() {

    }

    /**
     * @param $id
     */
    function __setId($id) {
        $this->id = intval($id);
    }

    /**
     * @param $stringName
     */
    function __setName($stringName) {
        $this->name = $stringName;
    }

    /**
     * @param $stringName
     */
    function __setFirstName($stringName) {
        $this->firstName = $stringName;
    }

    /**
     * @param $stringName
     */
    function __setLastName($stringName) {
        $this->lastName = $stringName;
    }

    /**
     * @param $id
     */
    function __setVendorId($id) {
        $this->vendorId = $id;
    }

    /**
     * @param $stringKey
     */
    function __setKey($stringKey) {
        $this->key = $stringKey;
    }

    /**
     * @param $level
     */
    function __setLevel($level) {
        $this->level = intval($level);
    }

    /**
     *  VOID
     */
    function __setAuthorized() {
        $this->authorized = true;
    }

    /**
     *  VOID
     */
    function __setUnAuthorized() {
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
    public function getName()
    {
        return $this->name;
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
     * @return boolean
     */
    public function isAuthorized()
    {
        return $this->authorized;
    }

}