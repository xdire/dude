<?php namespace Core;
use Core\Log\Log;

/**
 * Created by Anton Repin.
 * Date: 08.07.15
 * Time: 14:46
 */



class Call
{

    private static $viewfolder = '';
    private static $viewinitiated = false;

    private static function viewinit(){

        self::$viewfolder = O_APPPATH.'/View';
        self::$viewinitiated = true;

    }

    public static function view($view,$data=null,$return=false){

        if(!self::$viewinitiated){
            self::viewinit();
            if(session_status() === PHP_SESSION_NONE){
                session_start();
            };
        }

        try {

            $path = self::$viewfolder.'/'.$view.'.php';
            if(file_exists($path)) {

                if(is_array($data)){
                    foreach($data as $k=>$v){
                        $$k = $v;
                    }
                }
                $viewData = $data;
                if($return){
                    ob_start();
                    require($path);
                    return ob_get_clean();
                } else {
                    require($path);
                }

            }

        } catch (\Exception $e) {

            Log::append('[ERROR][IN VIEW] View '.$view.' cannot be loaded because: '.$e->getMessage());

        }

        return null;
    }

}