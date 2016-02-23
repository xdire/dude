<?php use Core\App;

/** ------------------- DEFINE ROUTING BELOW ---------------------- */

App::Route(ROUTE_ALL,'/',function ($req,$resp) {
    App::routeController('ExampleController@testRoute',$req,$resp);
});