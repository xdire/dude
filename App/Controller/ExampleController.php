<?php
/**
 * Created by Anton Repin.
 * Date: 2/22/16
 * Time: 10:59 PM
 */

namespace App\Controller;

use Core\Controller;
use Core\Server\Request;
use Core\Server\Response;

class ExampleController extends Controller
{

    public function test(){

        echo "Simple. Test task was finished.";

    }

    public function testRoute(Request $request, Response $response) {

        $response->send(200,"Test task was finished.");

    }

}