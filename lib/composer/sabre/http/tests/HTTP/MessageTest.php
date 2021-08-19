<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class MessageTest extends \PHPUnit\Framework\TestCase
{
    public function testConstruct()
    {
        $message = new MessageMock();
        $this->assertInstanceOf('Sabre\HTTP\Message', $message);
    }

    public function testStreamBody()
    {
        $body = 'foo';
        $h = fopen('php://memory', 'r+');
        fwrite($h, $body);
        rewind($h);

        $message = new MessageMock();
        $message->setBody($h);

        $this->assertEquals($body, $message->getBodyAsString());
        rewind($h);
        $this->assertEquals($body, stream_get_contents($message->getBodyAsStream()));
        rewind($h);
        $this->assertEquals($body, stream_get_contents($message->getBody()));
    }

    public function testStringBody()
    {
        $body = 'foo';

        $message = new MessageMock();
        $message->setBody($body);

        $this->assertEquals($body, $message->getBodyAsString());
        $this->assertEquals($body, stream_get_contents($message->getBodyAsStream()));
        $this->assertEquals($body, $message->getBody());
    }

    public function testCallbackBodyAsString()
    {
        $body = $this->createCallback('foo');

        $message = new MessageMock();
        $message->setBody($body);

        $string = $message->getBodyAsString();

        $this->assertSame('foo', $string);
    }

    public function testCallbackBodyAsStream()
    {
        $body = $this->createCallback('foo');

        $message = new MessageMock();
        $message->setBody($body);

        $stream = $message->getBodyAsStream();

        $this->assertSame('foo', stream_get_contents($stream));
    }

    public function testGetBodyWhenCallback()
    {
        $callback = $this->createCallback('foo');

        $message = new MessageMock();
        $message->setBody($callback);

        $this->assertSame($callback, $message->getBody());
    }

    /**
     * It's possible that streams contains more data than the Content-Length.
     *
     * The request object should make sure to never emit more than
     * Content-Length, if Content-Length is set.
     *
     * This is in particular useful when responding to range requests with
     * streams that represent files on the filesystem, as it's possible to just
     * seek the stream to a certain point, set the content-length and let the
     * request object do the rest.
     */
    public function testLongStreamToStringBody()
    {
        $body = fopen('php://memory', 'r+');
        fwrite($body, 'abcdefg');
        fseek($body, 2);

        $message = new MessageMock();
        $message->setBody($body);
        $message->setHeader('Content-Length', '4');

        $this->assertEquals(
            'cdef',
            $message->getBodyAsString()
        );
    }

    /**
     * Some clients include a content-length header, but the header is empty.
     * This is definitely broken behavior, but we should support it.
     */
    public function testEmptyContentLengthHeader()
    {
        $body = fopen('php://memory', 'r+');
        fwrite($body, 'abcdefg');
        fseek($body, 2);

        $message = new MessageMock();
        $message->setBody($body);
        $message->setHeader('Content-Length', '');

        $this->assertEquals(
            'cdefg',
            $message->getBodyAsString()
        );
    }

    public function testGetEmptyBodyStream()
    {
        $message = new MessageMock();
        $body = $message->getBodyAsStream();

        $this->assertEquals('', stream_get_contents($body));
    }

    public function testGetEmptyBodyString()
    {
        $message = new MessageMock();
        $body = $message->getBodyAsString();

        $this->assertEquals('', $body);
    }

    public function testHeaders()
    {
        $message = new MessageMock();
        $message->setHeader('X-Foo', 'bar');

        // Testing caselessness
        $this->assertEquals('bar', $message->getHeader('X-Foo'));
        $this->assertEquals('bar', $message->getHeader('x-fOO'));

        $this->assertTrue(
            $message->removeHeader('X-FOO')
        );
        $this->assertNull($message->getHeader('X-Foo'));
        $this->assertFalse(
            $message->removeHeader('X-FOO')
        );
    }

    public function testSetHeaders()
    {
        $message = new MessageMock();

        $headers = [
            'X-Foo' => ['1'],
            'X-Bar' => ['2'],
        ];

        $message->setHeaders($headers);
        $this->assertEquals($headers, $message->getHeaders());

        $message->setHeaders([
            'X-Foo' => ['3', '4'],
            'X-Bar' => '5',
        ]);

        $expected = [
            'X-Foo' => ['3', '4'],
            'X-Bar' => ['5'],
        ];

        $this->assertEquals($expected, $message->getHeaders());
    }

    public function testAddHeaders()
    {
        $message = new MessageMock();

        $headers = [
            'X-Foo' => ['1'],
            'X-Bar' => ['2'],
        ];

        $message->addHeaders($headers);
        $this->assertEquals($headers, $message->getHeaders());

        $message->addHeaders([
            'X-Foo' => ['3', '4'],
            'X-Bar' => '5',
        ]);

        $expected = [
            'X-Foo' => ['1', '3', '4'],
            'X-Bar' => ['2', '5'],
        ];

        $this->assertEquals($expected, $message->getHeaders());
    }

    public function testSendBody()
    {
        $message = new MessageMock();

        // String
        $message->setBody('foo');

        // Stream
        $h = fopen('php://memory', 'r+');
        fwrite($h, 'bar');
        rewind($h);
        $message->setBody($h);

        $body = $message->getBody();
        rewind($body);

        $this->assertEquals('bar', stream_get_contents($body));
    }

    public function testMultipleHeaders()
    {
        $message = new MessageMock();
        $message->setHeader('a', '1');
        $message->addHeader('A', '2');

        $this->assertEquals(
            '1,2',
            $message->getHeader('A')
        );
        $this->assertEquals(
            '1,2',
            $message->getHeader('a')
        );

        $this->assertEquals(
            ['1', '2'],
            $message->getHeaderAsArray('a')
        );
        $this->assertEquals(
            ['1', '2'],
            $message->getHeaderAsArray('A')
        );
        $this->assertEquals(
            [],
            $message->getHeaderAsArray('B')
        );
    }

    public function testHasHeaders()
    {
        $message = new MessageMock();

        $this->assertFalse($message->hasHeader('X-Foo'));
        $message->setHeader('X-Foo', 'Bar');
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    /**
     * @param string $content
     *
     * @return \Closure Returns a callback printing $content to php://output stream
     */
    private function createCallback($content)
    {
        return function () use ($content) {
            echo $content;
        };
    }
}

class MessageMock extends Message
{
}
