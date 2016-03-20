<?php namespace Xdire\Dude\Core\Server;

class Response
{

    private $code = 200;
    private $content = "";

    /**
     * Flush $content to connection
     * function will erase buffer before exec flush
     *
     * @param int $code
     * @param mixed $content
     */
    public function send($code, $content) {

        $this->code = $code;
        $this->content = $content;
        ob_clean();

        header("HTTP/1.0 ".$this->code);
        echo $content;

        ob_flush();

    }

    /**
     * Flush buffer to connection
     */
    public function flush(){
        ob_flush();
    }

    /**
     * FLush $content to connection and terminate program
     * some post operations will be executed in Kernel after this method will throw an Exception
     *
     * @param int $code
     * @param string | null $content
     * @throws \Exception
     */
    public function end($code, $content = null){
        header("HTTP/1.0 ".$code);
        if(!empty($content)) echo $content;
        ob_end_flush();
        throw new \Exception("Program exited",$code);
    }

    /**
     * @param string $path
     * @param mixed $text
     */
    public function route($path,$text) {
        header('Location: '.$path);
    }

}