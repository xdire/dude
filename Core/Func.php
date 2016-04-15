<?php
// ---------------------------------------------------------------------------------------------------------------------
/*
 *                                              ERROR HANDLING SECTION
 */
// ---------------------------------------------------------------------------------------------------------------------
/** Exception error handler */
set_error_handler("__eeh");
function __eeh($code, $msg, $file, $line ) {

    if(\Xdire\Dude\Core\App::getEnvironment() == 1) {

        echo "Error produced by application: \n";
        echo "Code: $code \nMessage: $msg \nFile: $file \nLine: $line";

        if(\Xdire\Dude\Core\App::getOutputType() == 1)
            ob_flush();
    }

    throw new ErrorException($msg, $code, 0, $file, $line);
}
/** Shutdown Fatal Error Function */
register_shutdown_function('__sdn');
function __sdn() {

    $isBuffered = \Xdire\Dude\Core\App::getOutputType();

    if($error = error_get_last()) {

        if($isBuffered == 1)
        ob_clean();

        header("HTTP/1.0 500");

        if(\Xdire\Dude\Core\App::getEnvironment() == 1) {
            echo "Error produced by application: \n";
            echo "Code: ".$error['type']." \nMessage: ".$error['message']." \nFile: ".$error['file']." \nLine: ".$error['line'];
        } else {
            $file = explode("/", $error['file']);
            $fileName = strstr( array_pop($file), '.', true);
            echo '{"errorCode":500,"errorMessage":"Error happened, be calm, send us a report and we\'ll fix it. ('.$fileName.':'.$error['line'].') "}';
        }

        if($isBuffered == 1)
            ob_flush();
    }

    if($isBuffered== 1)
        ob_end_clean();
}
// ---------------------------------------------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------------------------------------------

// ---------------------------------------------------------------------------------------------------------------------
/*
 *                                              DEBUGGING FUNCTIONS SECTION
 */
// ---------------------------------------------------------------------------------------------------------------------
function vd($variable) {

    $dt = debug_backtrace();
    $dt = array_reverse($dt);
    $pad = 0;
    echo "\n--------------------------------TRACE---------------------------------\n";
    $started = false;
    foreach($dt as $dlevel) {

        if($started) {

            for ($p = 0; $p < $pad; $p++) {
                echo " ";
            }
            if ($dlevel['function'] !== 'vd') {
                echo $dlevel["file"] . " -> " . $dlevel['line'] . " -> " . $dlevel['function'] . "\n";
            } else {
                echo $dlevel["file"] . " -> " . $dlevel['line'] . "\n";
            }
            $pad++;

        } else {

            if($dlevel['function'] == "init" && (strpos($dlevel['class'],'\Core\Kernel') !== false)){
                $started = true;
            }

        }

    }
    echo "--------------------------------DUMP----------------------------------\n";

    var_dump($variable);

    ob_flush();

}
function vdh($variable) {

    echo '<pre>';
    $dt = debug_backtrace();
    $dt = array_reverse($dt);
    $pad = 0;
    echo "--------------------------------TRACE---------------------------------<br>";
    $started = false;
    foreach($dt as $dlevel) {

        if($started) {

            for ($p = 0; $p < $pad; $p++) {
                echo "&nbsp;";
            }
            if ($dlevel['function'] !== 'vdh') {
                echo $dlevel["file"] . " -> " . $dlevel['line'] . " -> " . $dlevel['function'] . "<br>";
            } else {
                echo $dlevel["file"] . " -> " . $dlevel['line'] . "<br>";
            }
            $pad++;

        } else {

            if($dlevel['function'] == "init" && (strpos($dlevel['class'],'\Core\Kernel') !== false)){
                $started = true;
            }

        }

    }
    echo "--------------------------------DUMP----------------------------------<br>";

    var_dump($variable);
    echo '</pre>';
    ob_flush();

}
// ---------------------------------------------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------------------------------------------
// ---------------------------------------------------------------------------------------------------------------------

/**
 * @param $string
 * @return string : With fixed Apostrophe
 */
function stringEscape($string) {
    return str_replace("'", "\\'", $string);
}

/**
 * @param $string
 * @return string : Cleaned from all characters except numbers
 */
function stringStripNumbers($string){
    return preg_replace('/[^0-9.]+/', '', $string);
}


function getview($view,$data=null){

    $path = APPPATH.'/View/'.$view.'.php';

    if(file_exists($path)) {

        try {
            $viewData=$data;
            require($path);
        } catch (Exception $e) {
            echo 'View cannot be loaded';
        }

    }
}

function getSessionVar($var){

    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
    if(isset($_SESSION[$var])){
        return $_SESSION[$var];
    }
    return null;

}

function setSessionVar($var,$val){

    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }
    $_SESSION[$var] = $val;

    return true;

}

function setSessionError($errCode,$errMsg){

    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    $_SESSION['errorCode'] = $errCode;
    $_SESSION['errorMessage'] = $errMsg;

}

function clearSessionError(){
    $_SESSION['errorCode'] = null;
    $_SESSION['errorMessage'] = '';
}