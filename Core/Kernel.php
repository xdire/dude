<?php namespace Xdire\Dude\Core;

use Xdire\Dude\Core\Face\Middleware;
use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;
use Xdire\Dude\Core\Server\RouterPathHolder;
use Xdire\Dude\Core\Server\RouterPathObject;

abstract class Kernel {

    protected static $config=array();

    // Default Log path
    protected static $logpath='Data/Log';

    /** @var string|null */
    protected static $routeFilePath = null;
    /** @var string|null */
    protected static $processFilePath = null;

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
        'POST'=>2,'post'=>2,
        'GET'=>1,'get'=>1,
        'UPDATE'=>3,'update'=>3,
        'PATCH'=>3,'patch'=>3,
        'PUT'=>5,'put'=>5,
        'DELETE'=>4,'delete'=>4
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
     * @throws \Exception
     */
    public static function init(Array $config) {

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
            if(!defined("SYSTEM_PROCESS_ID")) define("SYSTEM_PROCESS_ID",null);
            if(!isset(self::$routeFilePath))
                throw new \Exception("No process file provided for serving local program executable",500);
            require(self::$routeFilePath);
        }

        if(session_status() === PHP_SESSION_ACTIVE){
            session_write_close();
        }

    }

    protected static function initRouting() {

        self::$routerMethod = (isset(self::$requestMethods[$_SERVER['REQUEST_METHOD']])) ? self::$requestMethods[$_SERVER['REQUEST_METHOD']] : 0;
        self::urlToPath($_SERVER['REQUEST_URI']);

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
     *
     *  @throws \Exception
     */
    protected static function routeCreatePath($method,$path,$callback=null,Middleware $middleware = null) {

        if(strlen($path) > 1) {

            $varsstarted = false;
            $pathes=explode('/',$path);
            /** @var RouterPathObject | null $currentRPO */
            $rootRPONumber = null;

            $rph = new RouterPathHolder();

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

                    // If variable parameters for route existed and started to filling into route
                    if(!$varsstarted) {

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
                                $tempLink = $rph->getCurrent()->routeObject->__addExtension($currentRout,$extRPO);
                                // Assign new RPO as next in the tree
                                $rph->addNext($tempLink);
                            }

                        }

                    }
                    // Throw exception if route format has syntax errors
                    else {
                        throw new \Exception('Router methods can\'t have variable in between route: '.$path, 67);
                        break;
                    }

                }
                // Route variables
                else
                {
                    $varsstarted = true;
                    $rph->getCurrent()->routeObject->__addName(ltrim($rout,'*'));
                }

            }

            // Write back to array only if $rootRPONumber was created — meaning the cell is NEW
            if($rootRPONumber != null)
                self::$routingPathObjects[$rootRPONumber] = $rph->getRootObject();

            // Get most deep available object in chain and assign Callbacks and MiddleFunctions (IF: Middleware)
            if(isset($rph->getCurrent()->routeObject)) {
                $rph->getCurrent()->routeObject->__addEvent($method,$callback);
                if($middleware !== null)
                    $rph->getCurrent()->routeObject->__setMiddleware($middleware);
            }

        } else {
            // If path is less than 1 symbol - check if this a root path
            if($path == "/") {
                self::$routingDictionary[$path] = self::$routingDictionaryCounter;
                $currentRPO = new RouterPathObject();
                self::$routingPathObjects[self::$routingDictionaryCounter] = $currentRPO;
                $currentRPO->__addEvent($method,$callback);
                if($middleware !== null)
                    $currentRPO->__setMiddleware($middleware);
                self::$routingDictionaryCounter++;
            }

        }

    }

    private static function routeUser() {

        if(self::$routerEnabled && self::$routingDictionaryCounter > 0) {

            /** @var RouterPathObject | null $routeRoot */
            $routeRoot = null;
            $aliasCounter = 0;
            $aliasStarted = false;

            foreach (self::$routerPath as $path => $v) {

                if(isset(self::$routingDictionary[$path])) {

                    $routeNum = self::$routingDictionary[$path];

                    if(!isset($routeRoot)) {

                        $routeRoot = self::$routingPathObjects[$routeNum];

                    } else {

                        if ($routeRoot->extensionsAmount > 0) {

                            if ($newRoot = $routeRoot->getExtensionForRouteId($routeNum)) {
                                $routeRoot = $newRoot;
                            }

                        }

                    }

                } else {

                    if(!$aliasStarted) {
                        self::doNotFound();
                        break;
                    }

                }

                if($aliasStarted && ($routeRoot->getAliasNamesAmount() > $aliasCounter)) {

                    if($name = $routeRoot->getAliasNameForCellNumber($aliasCounter)) {
                        self::$requestObject->__setPathParameter($name,$path);
                    }
                    $aliasCounter++;

                }

                if($routeRoot->extensionsAmount == 0) {
                    $aliasStarted = true;
                }

            }

            // Proceed to route
            if(isset($routeRoot)) {
                self::doRoute($routeRoot);
            } else {
                self::doRouteError(404);
            }

        } else {
            self::doRouteError(404);
        }

    }

    /**
     * Proceed with selected route
     * ------------------------------------------------------
     * @param RouterPathObject $route
     */
    private static function doRoute(RouterPathObject $route) {

        // Add query params to Request
        self::$requestObject->__setQueryParameters(self::$routerQuery);

        // Create instance of Response
        $response = new Response();

        // Put all data to output buffer for avoid any output except fired by Response->send();
        ob_start();

        // execute Middleware if one exists
        if($middleware = $route->getMiddleware()) {
            $middleware->start(self::$requestObject,$response);
        }

        // ------------------------------------------------------------------------------------
        // Start routing by selecting method binded callable
        // ------------------------------------------------------------------------------------

        // Select callable for current method
        if($func = $route->getEventForEventType(self::$routerMethod)) {
            if (is_callable($func)) {$func(self::$requestObject, $response);}
            else {self::doRouteError(500);}
        }
        // If callable is not found for current method then search for record in ALL
        elseif ($func = $route->getEventForEventType(0)){
            if (is_callable($func)) {$func(self::$requestObject,$response);}
            else {self::doRouteError(500);}
        }
        // Produce error if no methods found
        else {
            self::doRouteError(404);
        }

        // Clean all data which echoed to output stream not by Response->send()
        ob_end_clean();

    }

    private static function doRouteError($code) {

        switch($code) {

            case 404:
                header('X-PHP-Response-Code', true, 404);
                break;

            case 401:
                setSessionError(401,'Unauthorized operation cannot be completed');
                header('X-PHP-Response-Code', true, 401);
                App::routeTo('/');
                break;

            case 500:
                setSessionError(500,'Server error');
                header('X-PHP-Response-Code', true, 500);
                App::routeTo('/');
                break;


        }
        ob_end_flush();

    }

    private static function doNotFound() {
        header('X-PHP-Response-Code', true, 404);
    }

}