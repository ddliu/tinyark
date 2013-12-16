<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

/**
 * ArkHttpClient based on CURL
 *
 * $client = new ArkHttpClient($options);
 * $client->session(options)->get('http://example.com/index.html')->getContent();
 * options:
 *     - curl options:
 *         CURLOPT_URL or url...
 *     - other options:
 *         parent
 */
class ArkHttpClient
{
    protected $curl;

    protected $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => false,
        //CURLOPT_CONNECTTIMEOUT_MS => 10000,
        CURLOPT_MAXREDIRS => 5,
    );

    protected $sessionOptions = array();

    public function __construct($options = null)
    {
        if(null !== $options){
            $this->options = $options + $this->options;
        }
    }

    public function setOption($key, $value = null)
    {
        if(is_array($key)){
            $this->options = $key + $this->options;
        }
        else{
            $this->options[$key] = $value;
        }

        return $this;
    }

    public function session($key, $value = null)
    {
        if(is_array($key)){
            $this->sessionOptions = $key + $this->sessionOptions;
        }
        else{
            $this->sessionOptions[$key] = $value;
        }

        return $this;
    }

    /**
     * Generate CURL options from options and sessionOptions
     * 
     * @return array
     */
    protected function getCurlOptions()
    {
        $options = $this->sessionOptions + $this->options;
        $curl_options = $this->toCurlOptions($this->sessionOptions) + $this->toCurlOptions($this->options);

        //parent as referer
        if(!isset($curl_options[CURLOPT_REFERER]) && isset($options['parent'])){
            $curl_options[CURLOPT_REFERER] = $options['parent'];
        }

        return $curl_options;
    }

    protected function toCurlOptions($options)
    {
        $curl_options = array();
        foreach ($options as $key => $value) {
            if(is_integer($key)){
                $curl_options[$key] = $value;
            }
            else{
                if(defined('CURLOPT_'.strtoupper($key))){
                    $curl_options[constant('CURLOPT_'.strtoupper($key))] = $value;
                }
            }
        }

        return $curl_options;
    }

    protected function normalizeHeaders($headers){
        $result = array();
        foreach($headers as $k => $v){
            if(is_string($k)){
                $result[] = $k.':'.$v;
            }
            else{
                $result[] = $v;
            }
        }

        return $result;
    }

    protected function clearSession()
    {
        $this->sessionOptions = array();
    }

    /**
     * Send HTTP request
     * @todo  multipart support
     * 
     * @param  string $method Request method, GET/POST/PUT
     * @param  string $url    Request url
     * @param  mixed $params
     * @param  array $headers 
     * @return ArkHttpClientResponse
     */
    public function request($method, $url, $params = null, $headers = null, $multipart = false){
        $curl_options = $this->getCurlOptions();
        $options = $this->sessionOptions + $this->options;

        //fix url
        //remove anchor
        if(null !== $params && 'POST' !== $method){
            if(is_array($params)){
                $params = http_build_query($params);
            }
            $url .= ((false === strpos($url, '?'))?'?':'&').$params;
        }

        if(isset($options['parent'])){
            $url = self::normalizeUrl($options['parent'], $url);
        }

        $curl_options[CURLOPT_URL] = $url;

        if($method == 'POST'){
            $curl_options[CURLOPT_POST] = true;
            if(null !== $params){
                if($multipart && is_string($params)) {
                    parse_str($params, $params);
                }
                else if (!$multipart && is_array($params)) {
                    $params = http_build_query($params);
                }
                $curl_options[CURLOPT_POSTFIELDS] = $params;
            }
            else{
                $curl_options[CURLOPT_POSTFIELDS] = '';
            }
        }
        elseif($method == 'PUT'){
            $curl_options[CURLOPT_PUT] = true;
        }

        if($headers){
            if(isset($curl_options[CURLOPT_HTTPHEADER])){
                $curl_options[CURLOPT_HTTPHEADER] = $headers + $curl_options[CURLOPT_HTTPHEADER];
            }
            else{
                $curl_options[CURLOPT_HTTPHEADER] = $headers;
            }
        }

        if(isset($curl_options[CURLOPT_HTTPHEADER])){
            $curl_options[CURLOPT_HTTPHEADER] = $this->normalizeHeaders($curl_options[CURLOPT_HTTPHEADER]);
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $response_headers = null;
        $content = curl_exec($ch);

        $this->clearSession();

        $errno = curl_errno($ch);
        $error = curl_error($ch);

        if(!$errno){
            $info = curl_getinfo($ch);
        }
        else{
            $info = array();
        }

        if(isset($curl_options[CURLOPT_HEADER]) && $curl_options[CURLOPT_HEADER]){
            if(isset($info['header_size'])){
                $response_headers = substr($content, 0, $info['header_size']);
                $content = substr($content, $info['header_size']);
            }

            //note that following method is not reliable: http://stackoverflow.com/questions/11359276/php-curl-exec-returns-both-http-1-1-100-continue-and-http-1-1-200-ok-separated-b
            //list($response_headers, $content) = explode("\r\n\r\n", $content, 2);
        }

        return new ArkHttpClientResponse($response_headers, $content, $info, $errno, $error);
    }

    public function get($url, $params = null, $headers = null)
    {
        return $this->request('GET', $url, $params, $headers);
    }

    public function post($url, $params = null, $headers = null, $multipart = false)
    {
        return $this->request('POST', $url, $params, $headers = null, $multipart);
    }

    /**
     * Request with put method
     * @param  string $url
     * @param  array|string $params
     * @param  array $headers
     * @return ArkHttpClientResponse
     * @todo support put data
     */
    public function put($url, $params = null, $headers = null)
    {
        return $this->request('PUT', $url, $params, $headers = null);
    }

    /**
     * Normalize the request url
     * @see 
     * @param  string $parent Parent page url
     * @param  string $url    Url to be normalized
     * @return string
     */
    static public function normalizeUrl($parent, $url){
        //remove anchor
        if(false !== $anchor_pos = strpos($url, '#')){
            $url = substr($url, 0, $anchor_pos);
        }

        if(in_array(strtolower(substr($url, 0, 7)), array('http://', 'https:/'))){
            return $url;
        }
        $url = self::url_to_absolute($parent, $url);
        return $url;
    }

    /**
     * Combine a base URL and a relative URL to produce a new
     * absolute URL.  The base URL is often the URL of a page,
     * and the relative URL is a URL embedded on that page.
     *
     * This function implements the "absolutize" algorithm from
     * the RFC3986 specification for URLs.
     *
     * This function supports multi-byte characters with the UTF-8 encoding,
     * per the URL specification.
     *
     * Parameters:
     *  baseUrl     the absolute base URL.
     *
     *  url     the relative URL to convert.
     *
     * Return values:
     *  An absolute URL that combines parts of the base and relative
     *  URLs, or FALSE if the base URL is not absolute or if either
     *  URL cannot be parsed.
     */
    static public function url_to_absolute( $baseUrl, $relativeUrl )
    {
        if(substr($relativeUrl, 0, 2) == '//'){
            $relativeUrl = substr($relativeUrl, 1);
        }
        // If relative URL has a scheme, clean path and return.
        $r = self::split_url( $relativeUrl );
        if ( $r === FALSE )
            return FALSE;
        if ( !empty( $r['scheme'] ) )
        {
            if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
                $r['path'] = self::url_remove_dot_segments( $r['path'] );
            return self::join_url( $r );
        }

        // Make sure the base URL is absolute.
        $b = self::split_url( $baseUrl );
        if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
            return FALSE;
        $r['scheme'] = $b['scheme'];

        // If relative URL has an authority, clean path and return.
        if ( isset( $r['host'] ) )
        {
            if ( !empty( $r['path'] ) )
                $r['path'] = self::url_remove_dot_segments( $r['path'] );
            return self::join_url( $r );
        }
        unset( $r['port'] );
        unset( $r['user'] );
        unset( $r['pass'] );

        // Copy base authority.
        $r['host'] = $b['host'];
        if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
        if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
        if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

        // If relative URL has no path, use base path
        if ( empty( $r['path'] ) )
        {
            if ( !empty( $b['path'] ) )
                $r['path'] = $b['path'];
            if ( !isset( $r['query'] ) && isset( $b['query'] ) )
                $r['query'] = $b['query'];
            return self::join_url( $r );
        }

        // If relative URL path doesn't start with /, merge with base path
        if ( $r['path'][0] != '/' )
        {
            $base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
            if ( $base === FALSE ) $base = '';
            $r['path'] = $base . '/' . $r['path'];
        }
        $r['path'] = self::url_remove_dot_segments( $r['path'] );
        return self::join_url( $r );
    }

    /**
     * Filter out "." and ".." segments from a URL's path and return
     * the result.
     *
     * This function implements the "remove_dot_segments" algorithm from
     * the RFC3986 specification for URLs.
     *
     * This function supports multi-byte characters with the UTF-8 encoding,
     * per the URL specification.
     *
     * Parameters:
     *  path    the path to filter
     *
     * Return values:
     *  The filtered path with "." and ".." removed.
     */
    static public function url_remove_dot_segments( $path )
    {
        // multi-byte character explode
        $inSegs  = preg_split( '!/!u', $path );
        $outSegs = array( );
        foreach ( $inSegs as $seg )
        {
            if ( $seg == '' || $seg == '.')
                continue;
            if ( $seg == '..' )
                array_pop( $outSegs );
            else
                array_push( $outSegs, $seg );
        }
        $outPath = implode( '/', $outSegs );
        if ( $path[0] == '/' )
            $outPath = '/' . $outPath;
        // compare last multi-byte character against '/'
        if ( $outPath != '/' &&
            (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
            $outPath .= '/';
        return $outPath;
    }


    /**
     * This function parses an absolute or relative URL and splits it
     * into individual components.
     *
     * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
     * A portion of the ABNFs are repeated here:
     *
     *  URI-reference   = URI
     *          / relative-ref
     *
     *  URI     = scheme ":" hier-part [ "?" query ] [ "#" fragment ]
     *
     *  relative-ref    = relative-part [ "?" query ] [ "#" fragment ]
     *
     *  hier-part   = "//" authority path-abempty
     *          / path-absolute
     *          / path-rootless
     *          / path-empty
     *
     *  relative-part   = "//" authority path-abempty
     *          / path-absolute
     *          / path-noscheme
     *          / path-empty
     *
     *  authority   = [ userinfo "@" ] host [ ":" port ]
     *
     * So, a URL has the following major components:
     *
     *  scheme
     *      The name of a method used to interpret the rest of
     *      the URL.  Examples:  "http", "https", "mailto", "file'.
     *
     *  authority
     *      The name of the authority governing the URL's name
     *      space.  Examples:  "example.com", "user@example.com",
     *      "example.com:80", "user:password@example.com:80".
     *
     *      The authority may include a host name, port number,
     *      user name, and password.
     *
     *      The host may be a name, an IPv4 numeric address, or
     *      an IPv6 numeric address.
     *
     *  path
     *      The hierarchical path to the URL's resource.
     *      Examples:  "/index.htm", "/scripts/page.php".
     *
     *  query
     *      The data for a query.  Examples:  "?search=google.com".
     *
     *  fragment
     *      The name of a secondary resource relative to that named
     *      by the path.  Examples:  "#section1", "#header".
     *
     * An "absolute" URL must include a scheme and path.  The authority, query,
     * and fragment components are optional.
     *
     * A "relative" URL does not include a scheme and must include a path.  The
     * authority, query, and fragment components are optional.
     *
     * This function splits the $url argument into the following components
     * and returns them in an associative array.  Keys to that array include:
     *
     *  "scheme"    The scheme, such as "http".
     *  "host"      The host name, IPv4, or IPv6 address.
     *  "port"      The port number.
     *  "user"      The user name.
     *  "pass"      The user password.
     *  "path"      The path, such as a file path for "http".
     *  "query"     The query.
     *  "fragment"  The fragment.
     *
     * One or more of these may not be present, depending upon the URL.
     *
     * Optionally, the "user", "pass", "host" (if a name, not an IP address),
     * "path", "query", and "fragment" may have percent-encoded characters
     * decoded.  The "scheme" and "port" cannot include percent-encoded
     * characters and are never decoded.  Decoding occurs after the URL has
     * been parsed.
     *
     * Parameters:
     *  url     the URL to parse.
     *
     *  decode      an optional boolean flag selecting whether
     *          to decode percent encoding or not.  Default = TRUE.
     *
     * Return values:
     *  the associative array of URL parts, or FALSE if the URL is
     *  too malformed to recognize any parts.
     */
    static public function split_url( $url, $decode=FALSE)
    {
        // Character sets from RFC3986.
        $xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
        $xpchar        = $xunressub . ':@% ';

        // Scheme from RFC3986.
        $xscheme        = '([a-zA-Z][a-zA-Z\d+-.]*)';

        // User info (user + password) from RFC3986.
        $xuserinfo     = '((['  . $xunressub . '%]*)' .
                         '(:([' . $xunressub . ':%]*))?)';

        // IPv4 from RFC3986 (without digit constraints).
        $xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

        // IPv6 from RFC2732 (without digit and grouping constraints).
        $xipv6         = '(\[([a-fA-F\d.:]+)\])';

        // Host name from RFC1035.  Technically, must start with a letter.
        // Relax that restriction to better parse URL structure, then
        // leave host name validation to application.
        $xhost_name    = '([a-zA-Z\d-.%]+)';

        // Authority from RFC3986.  Skip IP future.
        $xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
        $xport         = '(\d*)';
        $xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
                     '?(:' . $xport . ')?)';

        // Path from RFC3986.  Blend absolute & relative for efficiency.
        $xslash_seg    = '(/[' . $xpchar . ']*)';
        $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
        $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
        $xpath_abs     = '(/(' . $xpath_rel . ')?)';
        $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
                 '|' . $xpath_rel . ')';

        // Query and fragment from RFC3986.
        $xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

        // URL.
        $xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
                         '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


        // Split the URL into components.
        if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
            return FALSE;

        if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);

        if ( !empty($m[7]) ) {
            if ( isset( $m[9] ) )   $parts['user']    = $m[9];
            else            $parts['user']    = '';
        }
        if ( !empty($m[10]) )       $parts['pass']    = $m[11];

        if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
        else if ( !empty($m[14]) )  $parts['host']    = $m[14];
        else if ( !empty($m[16]) )  $parts['host']    = $m[16];
        else if ( !empty( $m[5] ) ) $parts['host']    = '';
        if ( !empty($m[17]) )       $parts['port']    = $m[18];

        if ( !empty($m[19]) )       $parts['path']    = $m[19];
        else if ( !empty($m[21]) )  $parts['path']    = $m[21];
        else if ( !empty($m[25]) )  $parts['path']    = $m[25];

        if ( !empty($m[27]) )       $parts['query']   = $m[28];
        if ( !empty($m[29]) )       $parts['fragment']= $m[30];

        if ( !$decode )
            return $parts;
        if ( !empty($parts['user']) )
            $parts['user']     = rawurldecode( $parts['user'] );
        if ( !empty($parts['pass']) )
            $parts['pass']     = rawurldecode( $parts['pass'] );
        if ( !empty($parts['path']) )
            $parts['path']     = rawurldecode( $parts['path'] );
        if ( isset($h) )
            $parts['host']     = rawurldecode( $parts['host'] );
        if ( !empty($parts['query']) )
            $parts['query']    = rawurldecode( $parts['query'] );
        if ( !empty($parts['fragment']) )
            $parts['fragment'] = rawurldecode( $parts['fragment'] );
        return $parts;
    }


    /**
     * This function joins together URL components to form a complete URL.
     *
     * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
     * This function implements the specification's "component recomposition"
     * algorithm for combining URI components into a full URI string.
     *
     * The $parts argument is an associative array containing zero or
     * more of the following:
     *
     *  "scheme"    The scheme, such as "http".
     *  "host"      The host name, IPv4, or IPv6 address.
     *  "port"      The port number.
     *  "user"      The user name.
     *  "pass"      The user password.
     *  "path"      The path, such as a file path for "http".
     *  "query"     The query.
     *  "fragment"  The fragment.
     *
     * The "port", "user", and "pass" values are only used when a "host"
     * is present.
     *
     * The optional $encode argument indicates if appropriate URL components
     * should be percent-encoded as they are assembled into the URL.  Encoding
     * is only applied to the "user", "pass", "host" (if a host name, not an
     * IP address), "path", "query", and "fragment" components.  The "scheme"
     * and "port" are never encoded.  When a "scheme" and "host" are both
     * present, the "path" is presumed to be hierarchical and encoding
     * processes each segment of the hierarchy separately (i.e., the slashes
     * are left alone).
     *
     * The assembled URL string is returned.
     *
     * Parameters:
     *  parts       an associative array of strings containing the
     *          individual parts of a URL.
     *
     *  encode      an optional boolean flag selecting whether
     *          to do percent encoding or not.  Default = true.
     *
     * Return values:
     *  Returns the assembled URL string.  The string is an absolute
     *  URL if a scheme is supplied, and a relative URL if not.  An
     *  empty string is returned if the $parts array does not contain
     *  any of the needed values.
     */
    static public function join_url( $parts, $encode=FALSE)
    {
        if ( $encode )
        {
            if ( isset( $parts['user'] ) )
                $parts['user']     = rawurlencode( $parts['user'] );
            if ( isset( $parts['pass'] ) )
                $parts['pass']     = rawurlencode( $parts['pass'] );
            if ( isset( $parts['host'] ) &&
                !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
                $parts['host']     = rawurlencode( $parts['host'] );
            if ( !empty( $parts['path'] ) )
                $parts['path']     = preg_replace( '!%2F!ui', '/',
                    rawurlencode( $parts['path'] ) );
            if ( isset( $parts['query'] ) )
                $parts['query']    = rawurlencode( $parts['query'] );
            if ( isset( $parts['fragment'] ) )
                $parts['fragment'] = rawurlencode( $parts['fragment'] );
        }

        $url = '';
        if ( !empty( $parts['scheme'] ) )
            $url .= $parts['scheme'] . ':';
        if ( isset( $parts['host'] ) )
        {
            $url .= '//';
            if ( isset( $parts['user'] ) )
            {
                $url .= $parts['user'];
                if ( isset( $parts['pass'] ) )
                    $url .= ':' . $parts['pass'];
                $url .= '@';
            }
            if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
                $url .= '[' . $parts['host'] . ']'; // IPv6
            else
                $url .= $parts['host'];         // IPv4 or name
            if ( isset( $parts['port'] ) )
                $url .= ':' . $parts['port'];
            if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
                $url .= '/';
        }
        if ( !empty( $parts['path'] ) )
            $url .= $parts['path'];
        if ( isset( $parts['query'] ) )
            $url .= '?' . $parts['query'];
        if ( isset( $parts['fragment'] ) )
            $url .= '#' . $parts['fragment'];
        return $url;
    }

    /**
     * This function encodes URL to form a URL which is properly 
     * percent encoded to replace disallowed characters.
     *
     * RFC3986 specifies the allowed characters in the URL as well as
     * reserved characters in the URL. This function replaces all the 
     * disallowed characters in the URL with their repective percent 
     * encodings. Already encoded characters are not encoded again,
     * such as '%20' is not encoded to '%2520'.
     *
     * Parameters:
     *  url     the url to encode.
     *
     * Return values:
     *  Returns the encoded URL string. 
     */
    static public function encode_url($url) {
      $reserved = array(
        ":" => '!%3A!ui',
        "/" => '!%2F!ui',
        "?" => '!%3F!ui',
        "#" => '!%23!ui',
        "[" => '!%5B!ui',
        "]" => '!%5D!ui',
        "@" => '!%40!ui',
        "!" => '!%21!ui',
        "$" => '!%24!ui',
        "&" => '!%26!ui',
        "'" => '!%27!ui',
        "(" => '!%28!ui',
        ")" => '!%29!ui',
        "*" => '!%2A!ui',
        "+" => '!%2B!ui',
        "," => '!%2C!ui',
        ";" => '!%3B!ui',
        "=" => '!%3D!ui',
        "%" => '!%25!ui',
      );

      $url = rawurlencode($url);
      $url = preg_replace(array_values($reserved), array_keys($reserved), $url);
      return $url;
    }
}

class ArkHttpClientUA
{
    static public $knownUA = array(
        'IE6' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        'IE7' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
        'IE8' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)',
        'IE9' => 'Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.1)',
        'Firefox3' => 'Mozilla/5.0 (compatible; rv:1.9.1) Gecko/20090702 Firefox/3.5',
        'Firefox4' => 'Mozilla/5.0 (compatible; rv:2.0) Gecko/20110101 Firefox/4.0',
        'Firefox6' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0.2) Gecko/20100101 Firefox/6.0.2',
        'Chrome11' => 'Mozilla/5.0 (compatible) AppleWebKit/534.21 (KHTML, like Gecko) Chrome/11.0.682.0 Safari/534.21',
        'Safari5' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_7) AppleWebKit/534.16+ (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4',
        'Opera11' => 'Opera/9.80 (compatible; U) Presto/2.7.39 Version/11.00',
        'Maxthon3' => 'Mozilla/5.0 (compatible; U) AppleWebKit/533.1 (KHTML, like Gecko) Maxthon/3.0.8.2 Safari/533.1',
        'iPhone' => 'Mozilla/5.0 (iPhone; U; CPU OS 4_2_1 like Mac OS X) AppleWebKit/532.9 (KHTML, like Gecko) Version/5.0.3 Mobile/8B5097d Safari/6531.22.7',
        'iPad' => 'Mozilla/5.0 (iPad; U; CPU OS 4_2_1 like Mac OS X) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/4.0.2 Mobile/8C148 Safari/6533.18.5',
        'Android' => 'Mozilla/5.0 (Linux; U; Android 2.2) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
        //'Googlebot2' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        //'Msnbot1' => 'msnbot/1.1 (+http://search.msn.com/msnbot.htm)',
    );

    static public function getKnownByName($name){
        if(isset(self::$knownUA[$name])){
            return self::$knownUA[$name];
        }
        else{
            return false;
        }
    }
}

/**
 * Http client response object
 */
class ArkHttpClientResponse
{
    protected $errno;
    protected $error;
    protected $info;
    protected $headers;
    protected $content;

    /**
     * Constructor
     * @param string $content
     * @param array $info
     * @param integer $errno
     * @param string $error
     */
    public function __construct($headers, $content, $info, $errno, $error)
    {
        $this->headers = $headers;
        $this->content = $content;
        $this->info = $info;
        $this->errno = $errno;
        $this->error = $error;
    }

    public function getHeader($name = null)
    {
        if(null === $this->headers){
            return false;
        }
        if(!is_array($this->headers)){
            $headers = $this->headers;
            $this->headers = array();
            foreach (explode("\r\n", trim($headers)) as $i => $line) {
                if($i !== 0){
                    list($key, $value) = explode(': ', $line);
                    $this->headers[strtoupper($key)] = trim($value);
                }
            }
        }

        if(null === $name){
            return $this->headers;
        }
        else{
            $name = strtoupper($name);
            if(isset($this->headers[$name])){
                return $this->headers[$name];
            }
            else{
                return false;
            }
        }
    }

    public function __toString()
    {
        return $this->content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getInfo($key = null)
    {
        if(null === $key){
            return $this->info;
        }
        else{
            return isset($this->info[$key])?$this->info[$key]:null;
        }
    }

    /**
     * Get HTTP status code
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->getInfo('http_code');
    }

    public function getErrorNo()
    {
        return $this->errno;
    }

    public function getError()
    {
        return $this->error;
    }

    public function hasError($valid_code = 200)
    {
        if($this->getErrorNo()){
            return true;
        }

        $code = $this->getStatusCode();
        if(is_array($valid_code) && !in_array($code, $valid_code)){
            return true;
        }

        if(!is_array($valid_code) && $code != $valid_code){
            return true;
        }

        return false;
    }
}