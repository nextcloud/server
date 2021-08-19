<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class RequestTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $request = new Request('GET', '/foo', [
            'User-Agent' => 'Evert',
        ]);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/foo', $request->getUrl());
        $this->assertEquals([
            'User-Agent' => ['Evert'],
        ], $request->getHeaders());
    }

    public function testGetQueryParameters()
    {
        $request = new Request('GET', '/foo?a=b&c&d=e');
        $this->assertEquals([
            'a' => 'b',
            'c' => null,
            'd' => 'e',
        ], $request->getQueryParameters());
    }

    public function testGetQueryParametersNoData()
    {
        $request = new Request('GET', '/foo');
        $this->assertEquals([], $request->getQueryParameters());
    }

    /**
     * @backupGlobals
     */
    public function testCreateFromPHPRequest()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        $request = Sapi::getRequest();
        $this->assertEquals('PUT', $request->getMethod());
    }

    public function testGetAbsoluteUrl()
    {
        $r = new Request('GET', '/foo', [
            'Host' => 'sabredav.org',
        ]);

        $this->assertEquals('http://sabredav.org/foo', $r->getAbsoluteUrl());

        $s = [
            'HTTP_HOST' => 'sabredav.org',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
            'HTTPS' => 'on',
        ];

        $r = Sapi::createFromServerArray($s);

        $this->assertEquals('https://sabredav.org/foo', $r->getAbsoluteUrl());
    }

    public function testGetPostData()
    {
        $post = [
            'bla' => 'foo',
        ];
        $r = new Request('POST', '/');
        $r->setPostData($post);
        $this->assertEquals($post, $r->getPostData());
    }

    public function testGetPath()
    {
        $request = new Request('GET', '/foo/bar/');
        $request->setBaseUrl('/foo');
        $request->setUrl('/foo/bar/');

        $this->assertEquals('bar', $request->getPath());
    }

    public function testGetPathStrippedQuery()
    {
        $request = new Request('GET', '/foo/bar?a=B');
        $request->setBaseUrl('/foo');

        $this->assertEquals('bar', $request->getPath());
    }

    public function testGetPathMissingSlash()
    {
        $request = new Request('GET', '/foo');
        $request->setBaseUrl('/foo/');

        $this->assertEquals('', $request->getPath());
    }

    public function testGetPathOutsideBaseUrl()
    {
        $this->expectException('LogicException');
        $request = new Request('GET', '/bar/');
        $request->setBaseUrl('/foo/');

        $request->getPath();
    }

    public function testToString()
    {
        $request = new Request('PUT', '/foo/bar', ['Content-Type' => 'text/xml']);
        $request->setBody('foo');

        $expected = "PUT /foo/bar HTTP/1.1\r\n"
                  ."Content-Type: text/xml\r\n"
                  ."\r\n"
                  .'foo';
        $this->assertEquals($expected, (string) $request);
    }

    public function testToStringAuthorization()
    {
        $request = new Request('PUT', '/foo/bar', ['Content-Type' => 'text/xml', 'Authorization' => 'Basic foobar']);
        $request->setBody('foo');

        $expected = "PUT /foo/bar HTTP/1.1\r\n"
                  ."Content-Type: text/xml\r\n"
                  ."Authorization: Basic REDACTED\r\n"
                  ."\r\n"
                  .'foo';
        $this->assertEquals($expected, (string) $request);
    }
}
