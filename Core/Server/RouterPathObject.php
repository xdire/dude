<?php
/**
 * Created by Anton Repin.
 * Date: 2/16/16
 * Time: 12:54 PM
 */

namespace Core\Server;

use Core\Face\Middleware;

class RouterPathObject
{
    // Set of callable functions attached to
    // ALL
    // GET
    // POST etc
    public $attachedEvents = null;

    /** @var null|\SplFixedArray  */
    public $extensions = null;
    /** @var int  */
    public $extensionsAmount = 0;
    /** @var int  */
    public $extLen = 128;
    /** @var string[] */
    public $attachedNames = [];
    /** @var int */
    private $aNamesAmount = 0;
    /** @var bool */
    private $secured = false;
    /** @var null|Middleware */
    private $middleware = null;

    function __construct() {
        $this->attachedEvents = new \SplFixedArray(6);
        $this->extensions = new \SplFixedArray($this->extLen);
    }

    function &__addExtension($routeId, RouterPathObject $pathObject) {

        if($routeId > $this->extLen) {

            $newLen = (128 * (floor($routeId / 128)+1));
            $new = new \SplFixedArray($newLen);

            for($i=0;$i<$newLen;$i++){
                $new[$i]=$this->extensions[$i];
            }
            $this->extensions = $new;
            $this->extLen = $newLen;

        }

        $this->extensions[$routeId] = $pathObject;
        $this->extensionsAmount++;
        return $pathObject;

    }

    function &__getExtensionForRouteId($routeId){
        if($routeId < $this->extLen){
            return $this->extensions[$routeId];
        }
        return null;
    }

    function __checkExtenstionForRouteId($routeId){
        if($routeId < $this->extLen && isset($this->extensions[$routeId]))
            return true;
        return false;
    }

    function __addName($name) {
        $this->attachedNames[$this->aNamesAmount++] = $name;
    }

    function __addEvent($eventType,$closure) {
        $this->attachedEvents[$eventType] = $closure;
    }

    /**
     * @param $routeId
     * @return RouterPathObject|null
     */
    function getExtensionForRouteId($routeId) {

        if($routeId < $this->extLen){
            return $this->extensions[$routeId];
        }
        return null;

    }

    function getEventForEventType($eventType) {
        return $this->attachedEvents[$eventType];
    }

    function getAliasNameForCellNumber($number){
        if($number < $this->aNamesAmount){
            return $this->attachedNames[$number];
        }
        return null;
    }

    function getAllAliasNames(){
        if($this->aNamesAmount > 0) return $this->attachedNames; return null;
    }

    function getAliasNamesAmount(){
        return $this->aNamesAmount;
    }

    /** * @return Middleware|null */
    function getMiddleware(){
        if(isset($this->middleware)){
            return $this->middleware;
        }
        return null;
    }

    function __setAsSecured(){
        $this->secured = true;
    }

    function __setMiddleware(Middleware $m){
        $this->middleware = $m;
    }


}