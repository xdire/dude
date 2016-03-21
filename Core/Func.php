<?php
// ---------------------------------------------------------------------------------------------------------------------
/*
 *                                              ERROR HANDLING SECTION
 */
// ---------------------------------------------------------------------------------------------------------------------
/** Exception error handler */
set_error_handler("__eeh");
function __eeh($code, $msg, $file, $line ) {

    if(strpos(\Xdire\Dude\Core\App::getEnvironment(),'dev') !== false) {
        echo "Error produced by application: \n";
        echo "Code: $code \nMessage: $msg \nFile: $file \nLine: $line";
        ob_flush();
    }

    throw new ErrorException($msg, $code, 0, $file, $line);
}
/** Shutdown Fatal Error Function */
register_shutdown_function('__sdn');
function __sdn() {
    if($error = error_get_last()) {
        ob_clean();
        header("HTTP/1.0 500");
        $file = explode("/", $error['file']);
        $fileName = strstr( array_pop($file), '.', true);
        echo '{"errorCode":500,"errorMessage":"Error happened, be calm, send us a report and we\'ll fix it. ('.$fileName.':'.$error['line'].') "}';
        ob_flush();
    }
    ob_end_clean();
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
        } catch (Exception $e){
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
    return false;

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