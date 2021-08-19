<?php

namespace OpenStack\Test\Identity\v2\Models;

use OpenStack\Identity\v2\Api;
use OpenStack\Identity\v2\Models\Token;
use OpenStack\Test\TestCase;

class TokenTest extends TestCase
{
    private $token;

    public function setUp(): void
    {
        parent::setUp();

        $this->token = new Token($this->client->reveal(), new Api());
    }

    public function test_getting_id()
    {
        $this->token->id = 'foo';
        self::assertEquals('foo', $this->token->getId());
    }

    public function test_expiration_is_false_for_active_tokens()
    {
        $this->token->populateFromArray([
            'issued_at' => date('c'),
            'expires'   => date('c', strtotime('tomorrow'))
        ]);

        self::assertFalse($this->token->hasExpired());
    }

    public function test_expiration_is_true_for_old_tokens()
    {
        $this->token->populateFromArray([
            'issued_at' => date('c', strtotime('last wednesday')),
            'expires'   => date('c', strtotime('last thursday'))
        ]);

        self::assertTrue($this->token->hasExpired());
    }
}
