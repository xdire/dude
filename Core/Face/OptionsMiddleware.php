<?php namespace Xdire\Dude\Core\Face;

use Xdire\Dude\Core\Server\Request;
use Xdire\Dude\Core\Server\Response;

interface OptionsMiddleware
{
    public function start(Request $request, Response $response);
}