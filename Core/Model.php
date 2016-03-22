<?php namespace Xdire\Dude\Core;

use Xdire\Dude\Core\DB\DB;

abstract class Model {

    protected $DB;

    function __construct($instance = null) {
        $this->DB = new DB($instance);
    }

}