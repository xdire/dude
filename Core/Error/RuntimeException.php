<?php namespace Xdire\Dude\Core\Error;

class RuntimeException extends \Exception
{
    function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}