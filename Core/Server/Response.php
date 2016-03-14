<?php namespace Xdire\Dude\Core\Server;

class Response
{

    private $code = 200;
    private $content = "";

    /**
     * @param int $code
     * @param mixed $content
     */
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

    /**
     * @param int $code
     * @param mixed | null $text
     */
    public function end($code,$text=null){
        header("HTTP/1.0 ".$code);
        if(!empty($text)) echo $text;
        ob_end_flush();
        exit();
    }

    /**
     * @param string $path
     * @param mixed $text
     */
    public function route($path,$text) {
        header('Location: '.$path);
    }

}