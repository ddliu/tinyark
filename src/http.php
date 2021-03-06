<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class ArkRequest
{
    protected $attributes;

    public function __construct()
    {
        
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function getAttribute($key, $default = null)
    {
        return isset($this->attributes[$key])?$this->attributes[$key]:$default;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get request parameter($_GET > $_POST > attributes)
     * 
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($_GET[$key])?$_GET[$key]:(
                isset($_POST[$key])?$_POST[$key]:$this->getAttribute($key, $default)
            );
    }

    public function getClientIp()
    {
        return $_SERVER['HTTP_REMOTE_ADDR'];
    }

    /**
     * Check if the request is secure
     * 
     * @see http://stackoverflow.com/questions/1175096/how-to-find-out-if-you-are-using-https-without-serverhttps
     * @return boolean
     */
    public function isSecure()
    {
        if (
            $_SERVER['SERVER_PORT'] != 80
            && 
            (
                !empty($_SERVER['HTTPS']) 
                && 
                $_SERVER['HTTPS'] !== 'off'
                || 
                $_SERVER['SERVER_PORT'] == 443
            )
        ) {

            return true;
        }

        return false;
    }

    public function getScheme()
    {
        return $this->isSecure()?'https':'http';
    }

    public function getHost()
    {
        if (!$host = $_SERVER['HTTP_HOST']) {
            if (!$host = $_SERVER['SERVER_NAME']) {
                $host = $_SERVER['SERVER_ADDR'];
            }
        }

        // Remove port number from host
        $host = preg_replace('/:\d+$/', '', $host);

        // host is lowercase as per RFC 952/2181
        return trim(strtolower($host));
    }

    public function getPort()
    {
        return $_SERVER['SERVER_PORT'];
    }

    public function getUser()
    {
        return $_SERVER['PHP_AUTH_USER'];
    }

    public function getPassword()
    {
        return $_SERVER['PHP_AUTH_PW'];
    }

    public function getUserInfo()
    {
        $userinfo = $this->getUser();
        $password = $this->getPassword();
        if('' != $password){
            $userinfo .= ':'.$password;
        }

        return $userinfo;
    }

    public function getHttpHost()
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();
        if(($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)){
            return $this->getHost();
        }
        else{
            return $this->getHost().':'.$port;
        }
    }

    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * Get url base path of current request
     *     http://example.com/group/app/product-a.html => group/app
     * @return string
     */
    public function getBasePath()
    {
        $script_name = $_SERVER['SCRIPT_NAME'];
        $script_name_length = strlen($script_name);

        $request_uri = $_SERVER['REQUEST_URI'];

        $slash_pos = strrpos($script_name, '/');
        $base = substr($script_name, 0, $slash_pos);
        
        return $base;
    }

    public function isXmlHttpRequest()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

class ArkResponse
{
    protected $headers = array();

    protected $cookies = array();

    protected $version;

    protected $statusCode;

    protected $statusText;

    /**
     * @var string
     */
    protected $charset;

    public function __construct($content = '', $status = 200, $headers = array()) {
        $this->headers = $headers;
        $this->content = $content;
        $this->setStatusCode($status);
        $this->version = '1.0';
    }

    public function header($key, $value){
        $this->headers[$key] = $value;
    }

    public function setcookie()
    {
        $this->cookies[] = func_get_args();
    }

    public function setContent($content){
        $this->content = $content;
    }

    static public function getStatusTextByCode($code){
        /**
         * Status codes translation table.
         *
         * The list of codes is complete according to the
         * {@link http://www.iana.org/assignments/http-status-codes/ Hypertext Transfer Protocol (HTTP) Status Code Registry}
         * (last updated 2012-02-13).
         *
         * Unless otherwise noted, the status code is defined in RFC2616.
         */
        static $texts = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',            // RFC2518
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',          // RFC4918
            208 => 'Already Reported',      // RFC5842
            226 => 'IM Used',               // RFC3229
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Reserved',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',    // RFC-reschke-http-status-308-07
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',                                               // RFC2324
            422 => 'Unprocessable Entity',                                        // RFC4918
            423 => 'Locked',                                                      // RFC4918
            424 => 'Failed Dependency',                                           // RFC4918
            425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
            426 => 'Upgrade Required',                                            // RFC2817
            428 => 'Precondition Required',                                       // RFC6585
            429 => 'Too Many Requests',                                           // RFC6585
            431 => 'Request Header Fields Too Large',                             // RFC6585
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
            507 => 'Insufficient Storage',                                        // RFC4918
            508 => 'Loop Detected',                                               // RFC5842
            510 => 'Not Extended',                                                // RFC2774
            511 => 'Network Authentication Required',                             // RFC6585
        );
        $code = (int) $code;
        return isset($texts[$code])?$texts[$code]:'';
    }

    static public function getStatusMessageByCode($code){
        static $messages = array(
            400 => 'Your browser (or proxy) sent a request that this server could not understand.',
            401 => 'This server could not verify that you are authorized to access the URL. You either supplied the wrong credentials (e.g., bad password), or your browser doesn\'t understand how to supply the credentials required.',
            403 => 'You don\'t have permission to access the requested object. It is either read-protected or not readable by the server.',
            404 => 'The requested URL was not found on this server.',
            405 => 'The requested method is not allowed.',
            408 => 'The server closed the network connection because the browser didn\'t finish the request within the specified time.',
            413 => 'The request method does not allow the data transmitted, or the data volume exceeds the capacity limit.',
            415 => 'The server does not support the media type transmitted in the request.',
            500 => 'The server encountered an internal error and was unable to complete your request.',
            501 => 'The server does not support the action requested by the browser.',
            503 => 'The server is temporarily unable to service your request due to maintenance downtime or capacity problems. Please try again later.',
        );
        $code = (int) $code;
        return isset($messages[$code])?$messages[$code]:'';
    }

    public function setStatusCode($code, $text = null)
    {
        $this->statusCode = $code = (int) $code;
        if(null === $text){
            $text = self::getStatusTextByCode($code);
        }

        $this->statusText = $text;

        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function prepare()
    {
        $charset = $this->charset?$this->charset:'UTF-8';
        if(!isset($this->headers['Content-Type'])){
            $this->headers['Content-Type'] = 'text/html; charset='.$charset;
        }
        elseif(0 === strpos($this->headers['Content-Type'], 'text/') && false === strpos($this->headers['Content-Type'], 'charset')){
            $this->headers['Content-Type'].='; charset='.$charset;
        }

        return $this;
    }

    public function send(){
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    public function sendHeaders()
    {
        //status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        //headers
        foreach($this->headers as $key => $value){
            header($key.':'.$value, false);
        }
        //cookies
        foreach($this->cookies as $cookie){
            call_user_func_array('setcookie', $cookie);
        }

        return $this;
    }

    public function sendContent()
    {
        echo $this->content;

        return $this;
    }
}