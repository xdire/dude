<?php
/**
 * Created by Anton Repin.
 * Date: 29.05.15
 * Time: 16:44
 */

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

    $path = O_APPPATH.'/View/'.$view.'.php';

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