<?php

namespace OpenStack\Test\Common\Auth;

use GuzzleHttp\Psr7\Request;
use OpenStack\Common\Auth\AuthHandler;
use OpenStack\Common\Auth\Token;
use OpenStack\Test\TestCase;
use Psr\Http\Message\RequestInterface;

class AuthHandlerTest extends TestCase
{
    const TOKEN_ID = 'tokenId';

    private $generator;
    private $handler;

    public function setUp(): void
    {
        $this->generator = function () {
            $token = $this->prophesize(FakeToken::class);
            $token->getId()->shouldBeCalled()->willReturn(self::TOKEN_ID);
            return $token->reveal();
        };

        $this->handler = function (RequestInterface $r) {
            return $r;
        };

        $this->handler = new AuthHandler($this->handler, $this->generator);
    }

    public function test_it_should_bypass_auth_http_requests()
    {
        // Fake a Keystone request
        $request = new Request('POST', 'https://my-openstack.org:5000/v2.0/tokens');

        self::assertEquals($request, call_user_func_array($this->handler, [$request, []]));
    }

    public function test_it_should_generate_a_new_token_if_the_current_token_is_either_expired_or_not_set()
    {
        $token = $this->prophesize(Token::class);

        // force the mock token to indicate that its expired
        $token->getId()->willReturn('');
        $token->hasExpired()->willReturn(true);

        $request = new Request('GET', '');

        $handler = new AuthHandler($this->handler, $this->generator, $token->reveal());
        $handler($request, []);
    }
}

class FakeToken implements Token
{
    public function getId(): string
    {
    }

    public function hasExpired(): bool
    {
    }
}
