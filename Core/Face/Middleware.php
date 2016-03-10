<?php
/**
 * Created by Anton Repin.
 * Date: 2/17/16
 * Time: 12:26 AM
 */

namespace Xdire\Dude\Core\Face;

use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;

interface Middleware
{
    public function start(Request &$request,Response &$response);
}