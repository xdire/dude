<?php
/** -------------------------------------\
 *  DUDE! Application runnable command
 *  ----------------------------------- */
if(isset($argv)) {

    $runApp = false;
    echo "\nDude! framework Cli\n";
    /** ----------------------------
     *         PARSER SECTION
     *  --------------------------*/
    $cmd=[];
    $cmdCur = null;
    $i = 0;
    foreach($argv as $arg) {

        if($i > 0) {

            if ($arg[0] == "-") {
                $cmdCur = $arg[1];
                $cmd[$arg[1]] = "";
            } else {
                if($cmdCur != null){
                    $cmd[$cmdCur] = $arg;
                }
            }

        }
        $i++;

    }
    /** ----------------------------
     *          HELP SECTION
     *  --------------------------*/
    if(isset($cmd['h'])) {

        echo "\n------------------------\n   List of commands:\n------------------------\n";
        echo "\n-h \t\t : for help (this list)\n";
        echo "\n-p [value] \t : for create SYSTEM_PROCESS_ID constant with [value]\n\t\t";
        echo "this constant will be used in process.php file as id of some\n\t\tprocess to start\n";

        echo "\n\n";
        exit();
    }
    /** ----------------------------
     *         PROCESS SECTION
     *  --------------------------*/
    if(isset($cmd['p'])) {
        define("SYSTEM_PROCESS_ID",$cmd['p']);
        $runApp = true;
    }

    // Run app is allowed
    if($runApp) {
        require("app.php");
    }

} else {
    echo "Sorry this program can only run from the command prompt";
    exit();
}
