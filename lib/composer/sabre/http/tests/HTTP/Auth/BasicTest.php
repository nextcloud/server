<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class BasicTest extends \PHPUnit\Framework\TestCase
{
    public function testGetCredentials()
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'Basic '.base64_encode('user:pass:bla'),
        ]);

        $basic = new Basic('Dagger', $request, new Response());

        $this->assertEquals([
            'user',
            'pass:bla',
        ], $basic->getCredentials());
    }

    public function testGetInvalidCredentialsColonMissing()
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'Basic '.base64_encode('userpass'),
        ]);

        $basic = new Basic('Dagger', $request, new Response());

        $this->assertNull($basic->getCredentials());
    }

    public function testGetCredentialsNoheader()
    {
        $request = new Request('GET', '/', []);
        $basic = new Basic('Dagger', $request, new Response());

        $this->assertNull($basic->getCredentials());
    }

    public function testGetCredentialsNotBasic()
    {
        $request = new Request('GET', '/', [
            'Authorization' => 'QBasic '.base64_encode('user:pass:bla'),
        ]);
        $basic = new Basic('Dagger', $request, new Response());

        $this->assertNull($basic->getCredentials());
    }

    public function testRequireLogin()
    {
        $response = new Response();
        $request = new Request('GET', '/');

        $basic = new Basic('Dagger', $request, $response);

        $basic->requireLogin();

        $this->assertEquals('Basic realm="Dagger", charset="UTF-8"', $response->getHeader('WWW-Authenticate'));
        $this->assertEquals(401, $response->getStatus());
    }
}
