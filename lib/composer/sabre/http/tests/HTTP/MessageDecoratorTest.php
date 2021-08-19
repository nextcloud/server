<?php

declare(strict_types=1);

namespace Sabre\HTTP;

class MessageDecoratorTest extends \PHPUnit\Framework\TestCase
{
    protected $inner;
    protected $outer;

    public function setUp(): void
    {
        $this->inner = new Request('GET', '/');
        $this->outer = new RequestDecorator($this->inner);
    }

    public function testBody()
    {
        $this->outer->setBody('foo');
        $this->assertEquals('foo', stream_get_contents($this->inner->getBodyAsStream()));
        $this->assertEquals('foo', stream_get_contents($this->outer->getBodyAsStream()));
        $this->assertEquals('foo', $this->inner->getBodyAsString());
        $this->assertEquals('foo', $this->outer->getBodyAsString());
        $this->assertEquals('foo', $this->inner->getBody());
        $this->assertEquals('foo', $this->outer->getBody());
    }

    public function testHeaders()
    {
        $this->outer->setHeaders([
            'a' => 'b',
            ]);

        $this->assertEquals(['a' => ['b']], $this->inner->getHeaders());
        $this->assertEquals(['a' => ['b']], $this->outer->getHeaders());

        $this->outer->setHeaders([
            'c' => 'd',
        ]);

        $this->assertEquals(['a' => ['b'], 'c' => ['d']], $this->inner->getHeaders());
        $this->assertEquals(['a' => ['b'], 'c' => ['d']], $this->outer->getHeaders());

        $this->outer->addHeaders([
            'e' => 'f',
            ]);

        $this->assertEquals(['a' => ['b'], 'c' => ['d'], 'e' => ['f']], $this->inner->getHeaders());
        $this->assertEquals(['a' => ['b'], 'c' => ['d'], 'e' => ['f']], $this->outer->getHeaders());
    }

    public function testHeader()
    {
        $this->assertFalse($this->outer->hasHeader('a'));
        $this->assertFalse($this->inner->hasHeader('a'));
        $this->outer->setHeader('a', 'c');
        $this->assertTrue($this->outer->hasHeader('a'));
        $this->assertTrue($this->inner->hasHeader('a'));

        $this->assertEquals('c', $this->inner->getHeader('A'));
        $this->assertEquals('c', $this->outer->getHeader('A'));

        $this->outer->addHeader('A', 'd');

        $this->assertEquals(
            ['c', 'd'],
            $this->inner->getHeaderAsArray('A')
        );
        $this->assertEquals(
            ['c', 'd'],
            $this->outer->getHeaderAsArray('A')
        );

        $success = $this->outer->removeHeader('a');

        $this->assertTrue($success);
        $this->assertNull($this->inner->getHeader('A'));
        $this->assertNull($this->outer->getHeader('A'));

        $this->assertFalse($this->outer->removeHeader('i-dont-exist'));
    }

    public function testHttpVersion()
    {
        $this->outer->setHttpVersion('1.0');

        $this->assertEquals('1.0', $this->inner->getHttpVersion());
        $this->assertEquals('1.0', $this->outer->getHttpVersion());
    }
}
