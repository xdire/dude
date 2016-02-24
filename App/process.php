<?php use Core\App;
/* **********************************************************************
// ----------------------------------------------------------------------
//                        Specific App PROCESSES
// ----------------------------------------------------------------------
//
// If Request came from CRON or CLI and not detected as WEB then next
// code will be executed
// ----------------------------------------------------------------------
//                              USAGE
// ----------------------------------------------------------------------
// use value of constant SYSTEM_PROCESS_ID for determine which command
//                   was run with -p flag, at the start of the program
*/

if(SYSTEM_PROCESS_ID == "")
    App::useController('ExampleController@test');
else {
    echo "Process ID is not defined";
}

/* **********************************************************************
// ----------------------------------------------------------------------
//              Next Code Will be Executed after processes
// ----------------------------------------------------------------------
*/

// Get controller <ExampleController> and function <test> in this controller
