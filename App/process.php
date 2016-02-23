<?php use Core\App;
/* **********************************************************************
// ----------------------------------------------------------------------
//                        Specific App PROCESSES
// ----------------------------------------------------------------------
//
// If Request came from CRON or CLI and not detected as WEB then next
// code will be executed
*/

App::useController('ExampleController@test');

/* **********************************************************************
// ----------------------------------------------------------------------
//              Next Code Will be Executed after processes
// ----------------------------------------------------------------------
*/

// Get controller <ExampleController> and function <test> in this controller
