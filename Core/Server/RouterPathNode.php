<?php
/**
 * Created by Anton Repin.
 * Date: 2/22/16
 * Time: 1:22 AM
 */

namespace Xdire\Dude\Core\Server;


class RouterPathNode
{
    /** @var RouterPathNode  */
    public $next;
    /** @var RouterPathObject  */
    public $routeObject;
}