<?php
/**
 * Created by Anton Repin.
 * Date: 3/18/16
 * Time: 4:28 PM
 */

namespace Xdire\Dude\Core\FTP;

class FTPException extends \Exception
{

    /**
     * FTPException constructor.
     */
    public function __construct($message,$code)
    {
        parent::__construct($message,$code);
    }

}