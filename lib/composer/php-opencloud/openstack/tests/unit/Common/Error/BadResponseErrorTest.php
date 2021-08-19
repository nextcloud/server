<?php

namespace OpenStack\Test\Common\Error;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Test\TestCase;

class BadResponseErrorTest extends TestCase
{
    private $e;

    public function setUp(): void
    {
        $this->e = new BadResponseError();
    }

    public function test_it_gets_request()
    {
        $r = new Request('GET', '');

        $this->e->setRequest($r);
        self::assertEquals($this->e->getRequest(), $r);
    }

    public function test_it_gets_response()
    {
        $r = new Response(500);

        $this->e->setResponse($r);
        self::assertEquals($this->e->getResponse(), $r);
    }
}
