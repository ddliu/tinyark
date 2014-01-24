<?php
/**
 * Tinyark Framework
 *
 * @link http://github.com/ddliu/tinyark
 * @copyright  Liu Dong (http://codecent.com)
 * @license MIT
 */

class HttpClientTest extends PHPUnit_Framework_TestCase{
    public function testNormalizeUrl()
    {
        $url = ArkHttpClient::normalizeUrl('http://a.com', '/helloworld/a/b/..');
        $this->assertEquals($url, 'http://a.com/helloworld/a');

        $url = ArkHttpClient::normalizeUrl('http://a.com/cd/index.html', 'another.html');
        $this->assertEquals($url, 'http://a.com/cd/another.html');

        $url = ArkHttpClient::normalizeUrl('http://a.com/cd/index.html', '../../../another.html');
        $this->assertEquals($url, 'http://a.com/another.html');

        $url = ArkHttpClient::normalizeUrl('https://a.com/cd/index.html', '//another.html');
        $this->assertEquals($url, 'https://a.com/another.html');

        $url = ArkHttpClient::normalizeUrl('http://a.com/cd/index.html', 'http://b.com/ef/index.html');
        $this->assertEquals($url, 'http://b.com/ef/index.html');
    }

    public function testRequest()
    {
        $client = new ArkHttpClient();
        $response = $client->get('http://httpbin.org/get');
        $this->assertEquals($response->getStatusCode(), 200);
        $this->assertEquals($response->getInfo('url'), 'http://httpbin.org/get');
        $this->assertContains('"Host": "httpbin.org"', $response->getContent());

        $response = $client->get('http://httpbin.org/status/404');
        $this->assertEquals($response->getStatusCode(), 404);

        $this->assertEquals($response->hasError(), true);

        $this->assertEquals($response->hasError(array(200, 404)), false);

        $ua = 'Tinyark HTTP Client';
        $response = $client->get('http://httpbin.org/user-agent', null, array(
            'User-Agent' => $ua,
        ));
        $this->assertContains($ua, $response->getContent());
    }

}