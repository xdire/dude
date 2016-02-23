<?php
/** APPLICATION STARTER FILE Created by Anton Repin. Date: 18.05.15
 * ---------------------------------------------*/
/* ---------------------------------------------
|
|       SWITCH ENVIRONMENT CONSTANT
|
|       @values [dev], [prod]
|
------------------------------------------------*/
define('APPENVIRONMENT','dev');
// ---------------------------------------------
//      DEFINE CURRENT SERVER TIME ZONE
// ---------------------------------------------
date_default_timezone_set("America/New_York");
/* ---------------------------------------------
|
|       REQUIRE COMPOSER AUTOLOAD LIB
|
------------------------------------------------*/
require_once __DIR__ . '/vendor/autoload.php';

// SET APPLICATION ROOT PATH
// Changed to __FILE__ constant
#$baseName=basename(getcwd());
#if($baseName=='public'){chdir('../');}
chdir(dirname(__FILE__));

define('O_ROOTPATH',getcwd());
define('O_APPPATH',O_ROOTPATH.'/App');
define('O_CORPATH',O_ROOTPATH.'/Core');

// STARTING CORE
use Core\Core;
Core::init();