<?php
/**
 * Created by Anton Repin.
 * Date: 3/1/16
 * Time: 4:15 PM
 */

namespace Xdire\Dude\Core\Face;

use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;

interface RoutingController
{

    public function acceptRoute(Request $request,Response $response);

}