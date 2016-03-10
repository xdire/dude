<?php
/**
 * Created by Anton Repin.
 * Date: 09.06.15
 * Time: 10:33
 */
namespace Xdire\Dude\Xdire\Dude\Core;

use Xdire\Dude\Core\DB\DB;

abstract class Model {

    protected $DB;

    function __construct(){

        $this->DB = new DB();

    }

}