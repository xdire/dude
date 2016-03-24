<?php namespace Xdire\Dude\Core\Server;

use Xdire\Dude\Core\Face\Middleware;

/**
 * Router path object.
 * Each route will consist from the set of objects of this kind
 *
 * Class RouterPathObject
 * @package Xdire\Dude\Core\Server
 */
class RouterPathObject
{
    // Set of callable functions attached to
    // ALL
    // GET
    // POST etc
    /** @var null|\SplFixedArray  */
    public $attachedEvents = null;
    /** @var bool  */
    public $isVirtual = false;
    ///** @var int  */
    //public $attachedEventsAmount = 0;

    /** @var null|\SplFixedArray  */
    public $extensions = null;
    /** @var RouterPathObject | null */
    public $virtualExtInt = null;
    /** @var RouterPathObject | null */
    public $virtualExtStr = null;
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

    /**
     * Add extension of Route to RoutePathObject
     *
     * @param   int              $routeId
     * @param   RouterPathObject $pathObject
     * @return  RouterPathObject
     */
    public function &__addExtension($routeId, RouterPathObject $pathObject) {

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

    /**
     * TODO Function need to be verified against PHP behaviour to return ref from array and deleted afterwards
     * @param $routeId
     * @return mixed|null
     */
    public function &__getExtensionForRouteId($routeId){
        if($routeId < $this->extLen){
            return $this->extensions[$routeId];
        }
        return null;
    }

    /**
     * Check if extension is exist in this route
     *
     * @param int $routeId
     * @return bool
     */
    public function __checkExtenstionForRouteId($routeId){
        if($routeId < $this->extLen && isset($this->extensions[$routeId]))
            return true;
        return false;
    }

    /**
     * Add route variable to RoutePath /*routeVariable
     *
     * @param $name
     */
    public function __addName($name) {
        $this->attachedNames[$this->aNamesAmount++] = $name;
    }

    /**
     * Set callable event to some specified Event Type
     *
     * @param int $eventType
     * @param Callable $closure
     */
    public function __addEvent($eventType,callable $closure) {
        $this->attachedEvents[$eventType] = $closure;
    }

    /**
     * Retrieve extension for specified route
     *
     * @param $routeId
     * @return RouterPathObject | null
     */
    public function getExtensionForRouteId($routeId) {
        if($routeId < $this->extLen){
            return $this->extensions[$routeId];
        }
        return null;
    }

    /**
     * Return Callable attached to Event
     *
     * @param $eventType
     * @return Callable | null
     */
    public function getEventForEventType($eventType) {
        return $this->attachedEvents[$eventType];
    }

    /**
     * Return alias names specified with /*routeVariable for route
     * @param int $number
     * @return null|string
     */
    public function getAliasNameForCellNumber($number){
        if($number < $this->aNamesAmount){
            return $this->attachedNames[$number];
        }
        return null;
    }

    public function getAllAliasNames() {
        if($this->aNamesAmount > 0) return $this->attachedNames; return null;
    }

    public function getAliasNamesAmount(){
        return $this->aNamesAmount;
    }

    /** * @return Middleware|null */
    public function getMiddleware(){
        if(isset($this->middleware)){
            return $this->middleware;
        }
        return null;
    }

    public function __setAsSecured(){
        $this->secured = true;
    }

    /**
     * @param Middleware $m
     */
    public function __setMiddleware(Middleware $m){
        $this->middleware = $m;
    }


}