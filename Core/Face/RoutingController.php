<?php
/**
 * Created by Anton Repin.
 * Date: 3/1/16
 * Time: 4:15 PM
 */

namespace Core\Face;

use Core\Server\Request;
use Core\Server\Response;

interface RoutingController
{

    public function acceptRoute(Request $request,Response $response);

}