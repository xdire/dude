<?php
/**
 * Created by Anton Repin.
 * Date: 2/15/16
 * Time: 11:50 AM
 */

namespace Xdire\Dude\Core\Server;

class Response
{

    private $code = 200;
    private $content = "";

    public function send($code,$content) {

        $this->code = $code;
        $this->content = $content;
        ob_clean();

        header("HTTP/1.0 ".$this->code);
        echo $content;

        ob_flush();

    }

    public function flush(){
        ob_flush();
    }

    public function end($code,$text=null){
        header("HTTP/1.0 ".$code);
        if(!empty($text)) echo $text;
        ob_end_flush();
        exit();
    }

    public function route($path,$text) {
        header('Location: '.$path);
    }

}