<?php

namespace OpenStack\Test\Common\Transport;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils as Psr7Utils;
use OpenStack\Common\Transport\Utils;
use OpenStack\Test\TestCase;

class UtilsTest extends TestCase
{
    public function test_decoding_malformed_json_throws_error()
    {
        $response = new Response(200, [], Psr7Utils::streamFor('{'));
		$this->expectException(\InvalidArgumentException::class);

        Utils::jsonDecode($response);
    }

    public function test_it_adds_paths()
    {
        $uri = Utils::addPaths(Psr7Utils::uriFor('http://openstack.org/foo'), 'bar', 'baz', '1', '2');

        self::assertInstanceOf(Uri::class, $uri);
        self::assertEquals(Psr7Utils::uriFor('http://openstack.org/foo/bar/baz/1/2'), $uri);
    }
}
