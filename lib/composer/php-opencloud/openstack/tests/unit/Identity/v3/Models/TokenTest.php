<?php

namespace OpenStack\Test\Identity\v3\Models;

use OpenStack\Identity\v3\Api;
use OpenStack\Identity\v3\Models\Token;
use OpenStack\Test\TestCase;

class TokenTest extends TestCase
{
    private $token;

    public function setUp(): void
    {
        $this->rootFixturesDir = dirname(__DIR__);

        parent::setUp();

        $this->token = new Token($this->client->reveal(), new Api());
        $this->token->id = 'TOKEN_ID';
    }

    public function test_getting_id()
    {
        self::assertEquals('TOKEN_ID', $this->token->getId());
    }

    public function test_it_returns_false_if_expired()
    {
        $this->token->expires = new \DateTimeImmutable('yesterday');
        self::assertTrue($this->token->hasExpired());

        $this->token->expires = new \DateTimeImmutable('tomorrow');
        self::assertFalse($this->token->hasExpired());
    }

    public function test_it_throws_error_when_username_is_not_qualified_by_domain_id()
    {
		$this->expectException(\InvalidArgumentException::class);
        $this->token->create(['user' => ['name' => 'foo']]);
    }

    public function test_it_throws_error_when_neither_user_creds_or_token_id_is_provided()
    {
		$this->expectException(\InvalidArgumentException::class);
        $this->token->create([]);
    }
}
