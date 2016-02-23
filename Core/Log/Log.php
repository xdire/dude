<?php
/**
 * Created by PhpStorm.
 * User: xdire
 * Date: 20.05.15
 * Time: 10:54
 */

namespace Core\Log;
use Core\Core;

class Log extends Core{

    private static $logBuffer=array();
    private static $logStart=true;

    public static $disableOutput = true;

    private function __construct(){}

    public static function append($error,$time=true){

        date_default_timezone_set("America/New_York");
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

    private static function writeTodayLog($message){

        $file=self::callTodayLog();
        if(fwrite($file,$message) === false){

        }

    }

    private static function callTodayLog(){

        $date = date('Y-m-d');
        $filepath = O_ROOTPATH.'/'.self::$config['path_log'].'/'.$date.'.log';
        if(@$filepointer = fopen($filepath,"a+")){
            return $filepointer;
        } else {
            return false;
        }

    }

}