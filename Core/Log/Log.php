<?php
/**
 * Created by PhpStorm.
 * User: xdire
 * Date: 20.05.15
 * Time: 10:54
 */

namespace Xdire\Dude\Core\Log;

use Xdire\Dude\Core\Kernel;

class Log extends Kernel {

    private static $logBuffer=array();
    private static $logStart=true;
    private static $logError=false;
    private static $logErrorMsg="";
    public static $disableOutput = true;

    private function __construct(){}

    public static function append($error,$time=true) {

        if(self::$logStart){
            self::$logStart = false;
            array_push(self::$logBuffer,"-------------------- LOG STARTED --------------------- \r\n");
        }

        if($time){
            $error = '('.date('Ymd H:i:s').') : '. $error;
        }
        array_push(self::$logBuffer,$error. "\r\n");
        self::writeTodayLog($error. "\r\n");

    }

    public static function dump(){



    }

    public static function showHtml(){

        if(!self::$disableOutput) {
            $output = '<ul>';
            foreach (self::$logBuffer as $err) {
                $output .= '<li>' . $err . '</li>';
            }
            $output .= '</ul>';
            return $output;
        }

    }

    public static function show(){

        $output='';
        foreach(self::$logBuffer as $err){
            $output.=$err;
        }
        return $output;

    }

    private static function writeTodayLog($message) {

        if($file=self::callTodayLog()) {
            if (fwrite($file, $message) === false) {
                self::$logError = true;
                self::$logErrorMsg = "Log file cannot be written.";
                return false;
            }
            return true;
        } else {
            self::$logError = true;
            self::$logErrorMsg = "Log file cannot be open.";
        }
        return false;
    }

    private static function callTodayLog() {

        $date = date('Y-m-d');
        if(isset(self::$config)) {
            //$filepath = O_ROOTPATH . '/' . self::$config['path_log'] . '/' . $date . '.log';
            $filepath = O_ROOTPATH . '/Data/Log/' . $date . '.log';
            if (@$filepointer = fopen($filepath, "a+")) {
                return $filepointer;
            } else {
                return null;
            }
        }
        return null;

    }

}