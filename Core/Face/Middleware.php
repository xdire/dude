<?php
/**
 * Created by Anton Repin.
 * Date: 2/17/16
 * Time: 12:26 AM
 */

namespace Core\Face;

use Core\Server\Request;
use Core\Server\Response;

interface Middleware
{
    public function start(Request &$request,Response &$response);
}