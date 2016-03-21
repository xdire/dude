<?php namespace Xdire\Dude\Core;

use Xdire\Dude\Core\Face\Controller;
use Xdire\Dude\Core\Face\Middleware;
use Xdire\Dude\Core\Face\RoutingController;
use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;

final class App extends Kernel {

    protected function __construct(){}

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
     * @param Controller $controller
     *  - address of the controller in the App/Controllers
     *  + controller need to be implementing \Xdire\Dude\Core\Face\Controller Interface
     * ------------------------
     * @param mixed|null $data
     * - mixed type of variable or nothing
     *
     * @return void
     */
    final public static function useController(Controller $controller,$data=null) {

        $controller->start($data);

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

    /**
     * @return int|string
     */
    public static function getEnvironment(){
        return self::$env;
    }

}