<?php
/**
 * Created by Anton Repin.
 * Date: 3/1/16
 * Time: 10:37 PM
 */

namespace Xdire\Dude\Core\DB;

class DBException extends \Exception
{

    /**
     * DBException constructor.
     */
    public function __construct($message,$code)
    {
        parent::__construct($message,$code);
    }

}