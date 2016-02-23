<?php
/** APPLICATION STARTER FILE
 * -------------------------------------------
 * Created by Anton Repin.
 * Date: 18.05.15
 */

/* ------------------------------------------
|
|       REQUIRE COMPOSER AUTOLOAD LIB
|
---------------------------------------------*/
require_once __DIR__ . '/vendor/autoload.php';

/* ------------------------------------------
|
|       SWITCH ENVIRONMENT CONSTANT
|
|       @values [dev], [prod]
|
---------------------------------------------*/
define('APPENVIRONMENT','dev');

// SET APPLICATION ROOT PATH
// Changed to __FILE__ constant
#$baseName=basename(getcwd());
#if($baseName=='public'){chdir('../');}
chdir(dirname(__FILE__));

define('O_ROOTPATH',getcwd());
define('O_APPPATH',O_ROOTPATH.'/App');
define('O_CORPATH',O_ROOTPATH.'/Core');
define('O_LIBPATH',O_ROOTPATH.'/Lib');

// STARTING CORE
use Core\Core;
Core::init();