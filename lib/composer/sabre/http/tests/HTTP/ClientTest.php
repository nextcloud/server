<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateCurlSettingsArrayGET()
    {
        $client = new ClientMock();
        $client->addCurlSetting(CURLOPT_POSTREDIR, 0);

        $request = new Request('GET', 'http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_POSTREDIR => 0,
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_NOBODY => false,
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if (false === defined('HHVM_VERSION')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayHEAD()
    {
        $client = new ClientMock();
        $request = new Request('HEAD', 'http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_CUSTOMREQUEST => 'HEAD',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if (false === defined('HHVM_VERSION')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayGETAfterHEAD()
    {
        $client = new ClientMock();
        $request = new Request('HEAD', 'http://example.org/', ['X-Foo' => 'bar']);

        // Parsing the settings for this method, and discarding the result.
        // This will cause the client to automatically persist previous
        // settings and will help us detect problems.
        $client->createCurlSettingsArray($request);

        // This is the real request.
        $request = new Request('GET', 'http://example.org/', ['X-Foo' => 'bar']);

        $settings = [
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_NOBODY => false,
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if (false === defined('HHVM_VERSION')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayPUTStream()
    {
        $client = new ClientMock();

        $h = fopen('php://memory', 'r+');
        fwrite($h, 'booh');
        $request = new Request('PUT', 'http://example.org/', ['X-Foo' => 'bar'], $h);

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $h,
                CURLOPT_NOBODY => false,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if (false === defined('HHVM_VERSION')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testCreateCurlSettingsArrayPUTString()
    {
        $client = new ClientMock();
        $request = new Request('PUT', 'http://example.org/', ['X-Foo' => 'bar'], 'boo');

        $settings = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => false,
                CURLOPT_POSTFIELDS => 'boo',
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER => ['X-Foo: bar'],
                CURLOPT_URL => 'http://example.org/',
                CURLOPT_USERAGENT => 'sabre-http/'.Version::VERSION.' (http://sabre.io/)',
            ];

        // FIXME: CURLOPT_PROTOCOLS and CURLOPT_REDIR_PROTOCOLS are currently unsupported by HHVM
        // at least if this unit test fails in the future we know it is :)
        if (false === defined('HHVM_VERSION')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        $this->assertEquals($settings, $client->createCurlSettingsArray($request));
    }

    public function testIssue89MultiplePutInfileGivesWarning()
    {
        $client = new ClientMock();
        $tmpFile = tmpfile();
        $request = new Request('POST', 'http://example.org/', ['X-Foo' => 'bar'], 'body');

        $settings = $client->createCurlSettingsArray($request);
        $this->assertArrayNotHasKey(CURLOPT_PUT, $settings);
        $this->assertArrayNotHasKey(CURLOPT_INFILE, $settings);

        $request = new Request('POST', 'http://example.org/', ['X-Foo' => 'bar'], $tmpFile);

        $settings = $client->createCurlSettingsArray($request);
        $this->assertEquals(true, $settings[CURLOPT_PUT]);
        $this->assertEquals($tmpFile, $settings[CURLOPT_INFILE]);

        $request = new Request('POST', 'http://example.org/', ['X-Foo' => 'bar'], 'body');

        $settings = $client->createCurlSettingsArray($request);
        $this->assertArrayNotHasKey(CURLOPT_PUT, $settings);
        $this->assertArrayNotHasKey(CURLOPT_INFILE, $settings);
    }

    public function testSend()
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response) {
            $response = new Response(200);
        });

        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatus());
    }

    protected function getAbsoluteUrl($path)
    {
        $baseUrl = getenv('BASEURL');
        if ($baseUrl) {
            $path = ltrim($path, '/');

            return "$baseUrl/$path";
        }

        return false;
    }

    /**
     * @group ci
     */
    public function testSendToGetLargeContent()
    {
        $url = $this->getAbsoluteUrl('/large.php');
        if (!$url) {
            $this->markTestSkipped('Set an environment value BASEURL to continue');
        }

        $request = new Request('GET', $url);
        $client = new Client();
        $response = $client->send($request);

        $this->assertEquals(200, $response->getStatus());
        $this->assertLessThan(60 * pow(1024, 2), memory_get_peak_usage());
    }

    /**
     * @group ci
     */
    public function testSendAsync()
    {
        $url = $this->getAbsoluteUrl('/foo');
        if (!$url) {
            $this->markTestSkipped('Set an environment value BASEURL to continue');
        }

        $client = new Client();

        $request = new Request('GET', $url);
        $client->sendAsync($request, function (ResponseInterface $response) {
            $this->assertEquals("foo\n", $response->getBody());
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals(4, $response->getHeader('Content-Length'));
        }, function ($error) use ($request) {
            $url = $request->getUrl();
            $this->fail("Failed to GET $url");
        });

        $client->wait();
    }

    /**
     * @group ci
     */
    public function testSendAsynConsecutively()
    {
        $url = $this->getAbsoluteUrl('/foo');
        if (!$url) {
            $this->markTestSkipped('Set an environment value BASEURL to continue');
        }

        $client = new Client();

        $request = new Request('GET', $url);
        $client->sendAsync($request, function (ResponseInterface $response) {
            $this->assertEquals("foo\n", $response->getBody());
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals(4, $response->getHeader('Content-Length'));
        }, function ($error) use ($request) {
            $url = $request->getUrl();
            $this->fail("Failed to get $url");
        });

        $url = $this->getAbsoluteUrl('/bar.php');
        $request = new Request('GET', $url);
        $client->sendAsync($request, function (ResponseInterface $response) {
            $this->assertEquals("bar\n", $response->getBody());
            $this->assertEquals(200, $response->getStatus());
            $this->assertEquals('Bar', $response->getHeader('X-Test'));
        }, function ($error) use ($request) {
            $url = $request->getUrl();
            $this->fail("Failed to get $url");
        });

        $client->wait();
    }

    public function testSendClientError()
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response) {
            throw new ClientException('aaah', 1);
        });
        $called = false;
        $client->on('exception', function () use (&$called) {
            $called = true;
        });

        try {
            $client->send($request);
            $this->fail('send() should have thrown an exception');
        } catch (ClientException $e) {
        }
        $this->assertTrue($called);
    }

    public function testSendHttpError()
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response) {
            $response = new Response(404);
        });
        $called = 0;
        $client->on('error', function () use (&$called) {
            ++$called;
        });
        $client->on('error:404', function () use (&$called) {
            ++$called;
        });

        $client->send($request);
        $this->assertEquals(2, $called);
    }

    public function testSendRetry()
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');

        $called = 0;
        $client->on('doRequest', function ($request, &$response) use (&$called) {
            ++$called;
            if ($called < 3) {
                $response = new Response(404);
            } else {
                $response = new Response(200);
            }
        });

        $errorCalled = 0;
        $client->on('error', function ($request, $response, &$retry, $retryCount) use (&$errorCalled) {
            ++$errorCalled;
            $retry = true;
        });

        $response = $client->send($request);
        $this->assertEquals(3, $called);
        $this->assertEquals(2, $errorCalled);
        $this->assertEquals(200, $response->getStatus());
    }

    public function testHttpErrorException()
    {
        $client = new ClientMock();
        $client->setThrowExceptions(true);
        $request = new Request('GET', 'http://example.org/');

        $client->on('doRequest', function ($request, &$response) {
            $response = new Response(404);
        });

        try {
            $client->send($request);
            $this->fail('An exception should have been thrown');
        } catch (ClientHttpException $e) {
            $this->assertEquals(404, $e->getHttpStatus());
            $this->assertInstanceOf('Sabre\HTTP\Response', $e->getResponse());
        }
    }

    public function testParseCurlResult()
    {
        $client = new ClientMock();
        $client->on('curlStuff', function (&$return) {
            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];
        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_SUCCESS, $result['status']);
        $this->assertEquals(200, $result['http_code']);
        $this->assertEquals(200, $result['response']->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $result['response']->getHeaders());
        $this->assertEquals('Foo', $result['response']->getBodyAsString());
    }

    public function testParseCurlResultEmptyBody()
    {
        $client = new ClientMock();
        $client->on('curlStuff', function (&$return) {
            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];
        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\n";
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_SUCCESS, $result['status']);
        $this->assertEquals(200, $result['http_code']);
        $this->assertEquals(200, $result['response']->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $result['response']->getHeaders());
        $this->assertEquals('', $result['response']->getBodyAsString());
    }

    public function testParseCurlError()
    {
        $client = new ClientMock();
        $client->on('curlStuff', function (&$return) {
            $return = [
                [],
                1,
                'Curl error',
            ];
        });

        $body = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        $result = $client->parseCurlResult($body, 'foobar');

        $this->assertEquals(Client::STATUS_CURLERROR, $result['status']);
        $this->assertEquals(1, $result['curl_errno']);
        $this->assertEquals('Curl error', $result['curl_errmsg']);
    }

    public function testDoRequest()
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');
        $client->on('curlExec', function (&$return) {
            $return = "HTTP/1.1 200 OK\r\nHeader1:Val1\r\n\r\nFoo";
        });
        $client->on('curlStuff', function (&$return) {
            $return = [
                [
                    'header_size' => 33,
                    'http_code' => 200,
                ],
                0,
                '',
            ];
        });
        $response = $client->doRequest($request);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(['Header1' => ['Val1']], $response->getHeaders());
        $this->assertEquals('Foo', $response->getBodyAsString());
    }

    public function testDoRequestCurlError()
    {
        $client = new ClientMock();
        $request = new Request('GET', 'http://example.org/');
        $client->on('curlExec', function (&$return) {
            $return = '';
        });
        $client->on('curlStuff', function (&$return) {
            $return = [
                [],
                1,
                'Curl error',
            ];
        });

        try {
            $response = $client->doRequest($request);
            $this->fail('This should have thrown an exception');
        } catch (ClientException $e) {
            $this->assertEquals(1, $e->getCode());
            $this->assertEquals('Curl error', $e->getMessage());
        }
    }
}

class ClientMock extends Client
{
    protected $persistedSettings = [];

    /**
     * Making this method public.
     */
    public function receiveCurlHeader($curlHandle, $headerLine)
    {
        return parent::receiveCurlHeader($curlHandle, $headerLine);
    }

    /**
     * Making this method public.
     */
    public function createCurlSettingsArray(RequestInterface $request): array
    {
        return parent::createCurlSettingsArray($request);
    }

    /**
     * Making this method public.
     */
    public function parseCurlResult(string $response, $curlHandle): array
    {
        return parent::parseCurlResult($response, $curlHandle);
    }

    /**
     * This method is responsible for performing a single request.
     */
    public function doRequest(RequestInterface $request): ResponseInterface
    {
        $response = null;
        $this->emit('doRequest', [$request, &$response]);

        // If nothing modified $response, we're using the default behavior.
        if (is_null($response)) {
            return parent::doRequest($request);
        } else {
            return $response;
        }
    }

    /**
     * Returns a bunch of information about a curl request.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     */
    protected function curlStuff($curlHandle): array
    {
        $return = null;
        $this->emit('curlStuff', [&$return]);

        // If nothing modified $return, we're using the default behavior.
        if (is_null($return)) {
            return parent::curlStuff($curlHandle);
        } else {
            return $return;
        }
    }

    /**
     * Calls curl_exec.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     */
    protected function curlExec($curlHandle): string
    {
        $return = null;
        $this->emit('curlExec', [&$return]);

        // If nothing modified $return, we're using the default behavior.
        if (is_null($return)) {
            return parent::curlExec($curlHandle);
        } else {
            return $return;
        }
    }
}
