<?php namespace Xdire\Dude\Core\Server;

class Response
{
    /**
     * @var bool
     */
    private $contentSent = false;
    /**
     * @var bool
     */
    private $codeSent = false;
    /**
     * @var string
     */
    private $httpVer = "HTTP/1.1";
    /**
     * @var int
     */
    private $code = 200;
    /**
     * @var array
     */
    private $headers = [];

    /**
     * Response constructor.
     */
    function __construct()
    {

    }

    /**
     * Flush header to connection
     *
     * @param string $headerName
     * @param string $headerText
     */
    public function sendHeader($headerName, $headerText) {
        if(!$this->codeSent) {
            header($this->httpVer . " " . $this->code);
            $this->codeSent = true;
        }
        if(!$this->contentSent)
            header($headerName.": ".$headerText);
    }

    /**
     * Add header to flush
     *
     * @param string $headerName
     * @param string $headerText
     */
    public function addHeader($headerName, $headerText){
        $this->headers[$headerName] = $headerText;
    }

    /**
     * Flush $content to connection
     * function will erase buffer before exec flush
     *
     * @param int $code
     * @param mixed $content
     */
    public function send($code, $content) {

        $this->code = $code;

        ob_clean();

        $this->sendSrvInfo();

        echo $content;

        $this->contentSent = true;

        ob_flush();

    }

    /**
     * Flush buffer to connection
     *
     * After this function sending headers will be prohibited
     */
    public function flush() {
        $this->contentSent = true;
        $this->codeSent = true;
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
    public function end($code, $content = null) {

        $this->code = $code;
        // Clean
        ob_clean();
        // Process
        $this->sendSrvInfo();
        // Send content
        if(!empty($content)) {
            echo $content;
            $this->contentSent = true;
        }
        // Flush buffer
        ob_flush();
        // Throw back to Kernel
        throw new \Exception("",$code);

    }

    /**
     * @param string $path
     */
    public function route($path) {
        header('Location: '.$path);
    }

    private function sendSrvInfo(){
        if(!$this->codeSent) {

            http_response_code($this->code);
            $this->sendHeaders();
            $this->codeSent = true;

        }
    }
    private function sendHeaders(){
        foreach($this->headers as $headerName => $headerValue){
            header($headerName.": ".$headerValue);
        }
    }

}