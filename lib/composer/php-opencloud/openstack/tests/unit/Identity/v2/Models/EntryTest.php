<?php

namespace OpenStack\Test\Identity\v2\Models;

use OpenStack\Identity\v2\Api;
use OpenStack\Identity\v2\Models\Entry;
use OpenStack\Test\TestCase;

class EntryTest extends TestCase
{
    private $entry;

    public function setUp(): void
    {
        parent::setUp();

        $this->entry = new Entry($this->client->reveal(), new Api());
    }

    public function test_null_is_returned_when_no_endpoints_are_found()
    {
        self::assertEmpty($this->entry->getEndpointUrl('foo', 'bar'));
    }
}
