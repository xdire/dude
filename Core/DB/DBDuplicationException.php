<?php namespace Xdire\Dude\Core\DB;

class DBDuplicationException extends DBException
{
    function __construct($exceptionMessage, $exceptionCode, $dbMessage, $dbCode)
    {
        parent::__construct($exceptionMessage, $exceptionCode, $dbMessage, $dbCode);
    }
}