<?php namespace Xdire\Dude\Core;

use Xdire\Dude\Core\Face\Middleware;
use Xdire\Dude\Core\Face\OptionsMiddleware;
use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;
use Xdire\Dude\Core\Server\RouterPathHolder;
use Xdire\Dude\Core\Server\RouterPathObject;

/**
 * Dude Kernel Process Initializer
 *
 * Class defining launch variables and type of program
 * way of execution
 *
 * Class Kernel
 * @package Xdire\Dude\Core
 */
abstract class Kernel {

    /** @var array */
    protected static $config=array();
    /** @var string | int */
    protected static $env = "";
    /** @var int | null */
    protected static $envType = null;

    /** @var string|null */
    protected static $routeFilePath = null;
    /** @var string|null */
    protected static $processFilePath = null;

    // Type of execution mode [0 => Router, 1 => Cli]
    /** @var int */
    protected static $executionType = 0;
    // Type of output which program producing [0 => unbuffered, 1 => buffered]
    /** @var int */
    protected static $outputType = 0;

    // Routing related variables
    /** @var bool  */
    protected static $routerStarted = false;
    /** @var int|null  */
    protected static $routerMethod = null;
    /** @var array  */
    protected static $routerPath = array();
    /** @var array|null */
    protected static $routerQuery = null;

    protected static $routerEnabled = true;
    /** @var Request|null */
    protected static $requestObject = null;
    /** @var array  */
    private static $routingDictionary = [];
    /** @var int */
    private static $routingDictionaryCounter = 0;
    /** @var \Xdire\Dude\Core\Server\RouterPathObject[]  */
    private static $routingPathObjects = null;

    // Incoming request method translation to integer
    private static $requestMethods = array(
        'POST' => 2,'post' => 2,
        'GET' => 1,'get' => 1,
        'UPDATE' => 3,'update' => 3,
        'PATCH' => 3,'patch' => 3,
        'PUT' => 5,'put' => 5,
        'DELETE' => 4,'delete' => 4,
        'OPTIONS' => 6,'options' => 6
    );

    private function __construct(){}

    /**
     * @param string $location
     */
    public static function feedAppRouteFile($location){
        self::$routeFilePath = $location;
    }

    /**
     * @param string $location
     */
    public static function feedAppProcessFile($location){
        self::$processFilePath = $location;
    }

    /**
     * INIT Method - used in /app.php as method which init all framework to start
     *
     * @param array $config
     * @param mixed | null $env
     *
     * @throws \Exception
     */
    public static final function init(Array $config, $env = null) {

        // Set environment
        if(isset($env)) self::$env = $env;

        // Assign current config file
        self::$config = $config;

        // import all non namespaced (global NS) files
        require(__DIR__.'/Cons.php');
        require(__DIR__.'/Func.php');

        self::$routingPathObjects = new \SplFixedArray(1024);

        // Run processes defined in route.php
        if(!empty($_SERVER['REMOTE_ADDR']) && isset($_SERVER['REQUEST_METHOD'])) {

            self::initRouting();
            if(!isset(self::$routeFilePath))
                throw new \Exception("No route file provided for serving network requests",500);
            require(self::$routeFilePath);
            self::routeUser();

        }
        // Run processes defined in process.php
        else {
            self::$executionType = 1;
            if(!defined("SYSTEM_PROCESS_ID"))
                define("SYSTEM_PROCESS_ID",null);
            if(!isset(self::$routeFilePath))
                throw new \Exception("No process file provided for serving local program executable",500);
            require(self::$processFilePath);
        }
        // Let process be finished
        if(session_status() === PHP_SESSION_ACTIVE){
            session_write_close();
        }

    }

    /**
     * Routes initialization
     */
    protected static final function initRouting() {

        /* Define connection method */
        self::$routerMethod = (isset(self::$requestMethods[$_SERVER['REQUEST_METHOD']])) ? self::$requestMethods[$_SERVER['REQUEST_METHOD']] : 0;
        self::urlToPath($_SERVER['REQUEST_URI']);

        /* Create default empty root route for router */
        self::$routingDictionary["/"] = self::$routingDictionaryCounter;
        $RPO = new RouterPathObject();
        self::$routingPathObjects[self::$routingDictionaryCounter] = $RPO;
        self::$routingDictionaryCounter++;

    }

    /**
     * Begin to parse incoming URI
     * ------------------------------------------------------
     * @param string $url
     */
    private static function urlToPath($url) {

        self::$requestObject = new Request();

        # Clean Url to Part before ? Query and After
        $cleanedUri = strstr($url,'?',true);
        $cleanedQuery = null;
        if(!$cleanedUri) {
            $cleanedUri=$url;
        }
        else {
            $query = ltrim(strstr($url,'?'),'?');
            parse_str($query,$cleanedQuery);
        }

        # Explode Path
        $incomeUri = explode('/',$cleanedUri);
        $incomeLen = count($incomeUri);
        $resultRoute = array();
        $pathLast = null;
        $k=0;

        for($i=0; $i<$incomeLen; $i++) {

            $curPiece = $incomeUri[$i];

            if(strlen($curPiece) > 0){

                $qstr=explode('?',$curPiece);

                $resultRoute[$qstr[0]] = 1;

                $pathLast = $qstr[0];
                $k++;

            }

        }

        if($k==0)
            $resultRoute["/"] = 1;

        self::$requestObject->__setPath($cleanedUri);
        self::$requestObject->__setPathLastElement($pathLast);

        self::$routerQuery=$cleanedQuery;
        self::$routerPath=$resultRoute;
        self::$routerStarted=true;

    }

    /**
     *  Router Dispatcher Create Routes
     *  ------------------------------------------------------
     *  @param string $method
     *  @param string $path
     *  @param null | callable $callback
     *  @param Middleware $middleware
     *  @param OptionsMiddleware $optMiddleware
     *
     */
    protected static final function routeCreatePath(
        $method, $path, callable $callback=null, Middleware $middleware = null, OptionsMiddleware $optMiddleware = null) {

        if(strlen($path) > 1) {

            $varsstarted = false;
            $pathes = explode('/',$path);
            $pathesNum = count($pathes);

            /** @var RouterPathObject | null $currentRPO */
            $rootRPONumber = null;

            $rph = new RouterPathHolder();

            $i = 1;

            foreach($pathes as $rout) {

                $variable = strpos($rout,'*');
                if($variable === 0)
                    $variable = true;

                # Pass on empty route
                if($rout == "") {
                    continue;
                }

                // Parse objects which have no asterisk multiplier in body
                if(!$variable) {

                    // Set brahching process to exact (not virtual) extensions
                    $varsstarted = false;

                    /**
                     * Defined variable holding current route INT ID
                     * @var int|null $currentRout
                     */
                    $currentRout = null;

                    // 1. Add routes to indexing dictionary - for easy numeric access through all RPO objects
                    if(!isset(self::$routingDictionary[$rout])) {
                        self::$routingDictionary[$rout] = self::$routingDictionaryCounter;
                        $currentRout = self::$routingDictionaryCounter++;
                    } else {
                        $currentRout = self::$routingDictionary[$rout];
                    }

                    // 2. Check if Root Path is defined and if not - assign it to RoutePathHolder
                    if(!$rph->getRoot()) {
                        // 2.1. If null in array for INT currentRout index - create new RPO (will be saved later)
                        if(!isset(self::$routingPathObjects[$currentRout])) {

                            $currentRPO = new RouterPathObject();
                            $rootRPONumber = $currentRout;
                            $rph->addRoot($currentRPO);

                        }
                        // 2.2 Take reference for existing RPO if program has found one
                        else {

                            $currentRPO = &self::$routingPathObjects[$currentRout];
                            $rph->addRoot($currentRPO);

                        }

                    }
                    // 3. If Root path defined and next route particle exists then try to assign it
                    else {

                        // 3.1 If existed sub route assign it to NEXT in RoutePathHolder
                        if($rph->getCurrent()->routeObject->__checkExtenstionForRouteId($currentRout)) {
                            $rph->addNext($rph->getCurrent()->routeObject->__getExtensionForRouteId($currentRout));
                        }
                        // 3.2 If route not existed - create new RPO and assign it as extension
                        else
                        {
                            $extRPO = new RouterPathObject();
                            // Add extension to previous found RPO
                            $tempLink = $rph->getCurrent()->routeObject->__addExtension($currentRout, $extRPO);
                            // Assign new RPO as next in the tree
                            $rph->addNext($tempLink);
                        }

                    }

                }
                // Create virtual route if it's variable
                else
                {

                    // Add alias for name
                    $rph->getCurrent()->routeObject->__addName(ltrim($rout, '*'));

                    // Check if virutal extension is not exist
                    if(!isset($rph->getCurrent()->routeObject->virtualExtStr)){

                        // Add only one virtual extension after not virtual one
                        if(!$varsstarted) {

                            $extRPO = new RouterPathObject();
                            $extRPO->isVirtual = true;
                            $rph->getCurrent()->routeObject->virtualExtStr = $extRPO;
                            $rph->addNext($rph->getCurrent()->routeObject->virtualExtStr);

                            // Set branching process to add variables to virtual extension
                            $varsstarted = true;

                        }

                    }
                    // Take existing virtual route if defined for that rout
                    else {

                        $rph->addNext($rph->getCurrent()->routeObject->virtualExtStr);
                        $varsstarted = true;

                    }

                }

                $i++;

            }

            // Write back to array only if $rootRPONumber was created — meaning the cell is NEW
            if($rootRPONumber != null)
                self::$routingPathObjects[$rootRPONumber] = $rph->getRootObject();

            // Get most deep available object in chain and assign Callbacks and MiddleFunctions (IF: Middleware)
            if(isset($rph->getCurrent()->routeObject)) {

                if($i == $pathesNum) {

                    $rph->getCurrent()->routeObject->__addEvent($method, $callback);

                    if ($middleware !== null)
                        $rph->getCurrent()->routeObject->__setMiddleware($middleware);
                    if (isset($optMiddleware))
                        $rph->getCurrent()->routeObject->__setAttachedOptEvent($optMiddleware);

                }

            }

        } else {

            // If path is a root path, then apply events to the root path
            if($path == "/") {

                $currentRout = self::$routingDictionary[$path];
                $currentRPO = &self::$routingPathObjects[$currentRout];

                $currentRPO->__addEvent($method, $callback);

                // Add Middleware to Root Path
                if($middleware !== null)
                    $currentRPO->__setMiddleware($middleware);
                // Add Options Middleware to Root Path
                if (isset($optMiddleware))
                    $currentRPO->__setAttachedOptEvent($optMiddleware);

            }

        }

    }

    /**
     *  Dispatch incoming request trough routing tree
     */
    private static final function routeUser() {

        // Check if request is at least suitable to resolve
        if(self::$routingDictionaryCounter != 0) {

            /** @var RouterPathObject | null $routeRoot */
            $routeRoot = null;
            $aliasCounter = 0;

            foreach (self::$routerPath as $path => $v) {

                // If current route path is in dictionary of exact routes
                if(isset(self::$routingDictionary[$path])) {

                    $routeNum = self::$routingDictionary[$path];

                    // Start routing from root
                    if(!isset($routeRoot)) {

                        $routeRoot = self::$routingPathObjects[$routeNum];

                    }
                    // Get exact route
                    else if ($newRoot = $routeRoot->getExtensionForRouteId($routeNum)) {

                        $routeRoot = $newRoot;
                        $aliasCounter=0;

                    }
                    // Check virtual route if exact not found
                    else if (isset($routeRoot->virtualExtStr)) {

                        if($name = $routeRoot->getAliasNameForCellNumber($aliasCounter)) {
                            self::$requestObject->__setPathParameter($name, $path);
                            $aliasCounter++;
                        }
                        $routeRoot = $routeRoot->virtualExtStr;
                        $aliasCounter=0;

                    }

                }
                // If current route is definitely some variable parameter
                else {

                    // Put Variable into Request Path Parameter with attached variable
                    if($name = $routeRoot->getAliasNameForCellNumber($aliasCounter)) {

                        self::$requestObject->__setPathParameter($name, $path);
                        $aliasCounter++;
                    }

                    // If exists virtual route for this branch - use it
                    if (isset($routeRoot->virtualExtStr)) {
                        $routeRoot = $routeRoot->virtualExtStr;
                        $aliasCounter = 0;
                    }

                }

            }

            // Proceed to route
            if(isset($routeRoot)) {
                self::doRoute($routeRoot);
                return;
            }

        }

        // Produce not found in all failed cases
        self::doRouteError(404);

    }

    /**
     * Proceed with selected route
     * ------------------------------------------------------
     * @param RouterPathObject $route
     */
    private static final function doRoute(RouterPathObject $route) {

        // Add query params to Request
        self::$requestObject->__setQueryParameters(self::$routerQuery);

        // Create instance of Response
        $response = new Response();

        // Put all data to output buffer for avoid any output except fired by Response->send();
        ob_start();
        // Set flag that output is buffered
        self::$outputType = 1;

        // ------------------------------------------------------------------------------------
        // Start routing through Middleware if it's exists for this route
        //
        // Middleware need to continue route with executing next($req,$res)
        // ------------------------------------------------------------------------------------

        if($middleware = $route->getMiddleware()) {

            try {

                // Run Specific REST Method if Attached to PATH
                if($func = $route->getEventForEventType(self::$routerMethod)) {

                    $middleware->start(self::$requestObject,$response,$func);

                }
                // Run ROUTE_ALL event if Attached to PATH
                elseif ($func = $route->getEventForEventType(0)) {

                    $middleware->start(self::$requestObject,$response,$func);

                }
                // Run OPTIONS request Middleware if any attached
                elseif (self::$routerMethod == 6) {

                    if($omw = $route->getAttachedOptEvent()) {
                        $omw->start(self::$requestObject,$response);
                    }

                }
                // Make Error NOT FOUND if nothing matched
                else {
                    self::doRouteError(404);
                }

            } catch (\Exception $e) {

                self::doRouteError($e->getCode(), $e->getMessage());
                ob_clean();
                return;

            }

        }
        else {

        // ------------------------------------------------------------------------------------
        // If no middleware was defined for this route then
        //
        // Start routing by selecting method binded callable
        // ------------------------------------------------------------------------------------

            // Select callable for current method
            if ($func = $route->getEventForEventType(self::$routerMethod)) {

                try {
                    $func(self::$requestObject, $response);
                } catch (\Exception $e) {
                    self::doRouteError($e->getCode(), $e->getMessage());
                }

            }
            // If callable is not found for current method then search for record in ALL
            elseif ($func = $route->getEventForEventType(0)) {

                try {
                    $func(self::$requestObject, $response);
                } catch (\Exception $e) {
                    self::doRouteError($e->getCode(), $e->getMessage());
                }

            }
            elseif (self::$routerMethod == 6) {

                if($omw = $route->getAttachedOptEvent()) {
                    $omw->start(self::$requestObject, $response);
                }

            }
            // Produce error if no methods found
            else {
                self::doRouteError(404);
            }

        }

        // Clean all data which echoed to output stream not by Response->send()
        ob_clean();

    }

    /**
     * Handle standard set of errors
     *
     * @param int $code
     * @param string $message
     */
    private static function doRouteError($code, $message = "") {

        if(self::whichEnv() == 0) $message = "";

        switch($code) {

            case 0:
            case 500:

                http_response_code(500);
                break;

            case 404:

                http_response_code(404);
                break;

            case 401:

                http_response_code(401);
                break;

            default:

                http_response_code($code);
                break;

        }

        echo $message;
        ob_flush();
        ob_clean();

    }

    /**
     * Returns 1 for dev environments
     * Returns 0 for other environments
     *
     * @return int|null
     */
    protected static function whichEnv() {

        if(!isset(self::$envType)) {
            if (strpos(self::$env, 'dev') !== false) {
                self::$envType = 1;
                return 1;
            }
            return 0;
        } else
            return self::$envType;

    }

}