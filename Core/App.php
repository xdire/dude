<?php
/**
 * Created by Anton Repin
 * User: xdire
 * Date: 19.05.15
 * Time: 9:54
 */

namespace Xdire\Dude\Core;

use Xdire\Dude\Core\Face\Middleware;
use Xdire\Dude\Core\Face\RoutingController;
use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;

final class App extends Core {

    protected function __construct(){}
    /** @var null | User */
    public static $user = null;

    /**
     * Get some parameter from config, if it exists
     * @param $param
     * @return null
     */
    public static function getConfig($param){

        if(isset(self::$config[$param])){
            return self::$config[$param];
        } else {
            return null;
        }

    }

    /**
     * Calling Controller within application
     *
     * ------------------------
     * @param string $statement
     *  - address of the controller in the App/Controllers
     *  * example: SomeFolder\Controller@someMethod
     * ------------------------
     * @param mixed|null $data
     * - mixed type of variable or nothing
     *
     * @return bool
     */
    final public static function useController($statement,$data=null){

        $expClass=explode('@',$statement);
        $strClass="\\App\\Controller\\".$expClass[0];
        $strFunc=(isset($expClass[1]))?$expClass[1]:'';

        if($ctr=new $strClass()){

            if(strlen($strFunc)>0 && method_exists($ctr,$strFunc)){
                if(isset($data)) {
                    $ctr->$strFunc($data);
                } else {
                    $ctr->$strFunc();
                }
            }
            return $ctr;

        } else return false;

    }

    /**
     * Calling Controller within route file
     *
     * ---------------------------
     * @param string   $statement
     * - address of the controller in the App/Controllers
     * * example: SomeFolder\Controller@someMethod
     * ---------------------------
     * @param Request  $req
     * - System Request object which contain all useful
     * information about incoming Request
     * ---------------------------
     * @param Response $res
     * - System Response object which has methods to flush
     * data back to connection
     *
     * @return bool
     */
    final public static function routeController($statement, Request $req, Response $res){

        $expClass=explode('@',$statement);
        $strClass="\\App\\Controller\\".$expClass[0];
        $strFunc=(isset($expClass[1]))?$expClass[1]:'';

        if($ctr=new $strClass()){

            if(strlen($strFunc)>0 && method_exists($ctr,$strFunc)){
                $ctr->$strFunc($req,$res);
            }
            return $ctr;

        } else return false;

    }

    /**
     * Calling Controller of RoutingController Interface within route file
     *
     * ---------------------------
     * @param RoutingController $controller
     * - address of the controller in the App/Controllers
     * + controller need to be implementing \Xdire\Dude\Core\Face\RoutingController Interface
     * * example: SomeFolder\Controller
     * ---------------------------
     * @param Request  $req
     * - System Request object which contain all useful
     * information about incoming Request
     * ---------------------------
     * @param Response $res
     * - System Response object which has methods to flush
     * data back to connection
     *
     * @return void
     */
    final public static function routeNextController(RoutingController $controller, Request $req, Response $res){

        /** @var \Xdire\Dude\Core\Face\RoutingController $ctr */
        $controller->acceptRoute($req,$res);

    }

    final public static function useModel($statement) {

        $strClass="\\App\\Model\\".$statement;

        if($model=new $strClass()) {

            return $model;

        } else return false;

    }

    /**
     * Create static Route in routing file
     *
     * ----------------------------------
     * @param int             $method
     * - 1,2,3,4,5 : ALL,GET,POST,UPDATE,DELETE,PUT
     * ----------------------------------
     * @param string          $route
     * - string route if following types:
     * --->  "/api/get/something"
     * --->  "/user/*section/*page
     *
     * Where *section & *page — variable aliases which will be catched as
     * ['section'] & ['page'] into Request object
     * ----------------------------------
     * @param null            $callback
     * - callback which will be executed after the route will fetch for
     * current request. Callable function.
     * ----------------------------------
     * @param Middleware|null $middleware
     * - middleware function, implementing the Middleware Interface
     *
     * Will be executed on before the Request reach the callback
     * Can finish the request immediately with $response->end();
     *
     * @throws \Exception
     */
    final public static function route($method,$route,$callback=null,Middleware $middleware=null) {

        self::routeCreatePath($method,$route,$callback,$middleware);

    }

    final public static function routeTo($route){
        header('Location: '.$route);
    }

    public static function disableRouter(){
        self::$routerEnabled=false;
    }

}