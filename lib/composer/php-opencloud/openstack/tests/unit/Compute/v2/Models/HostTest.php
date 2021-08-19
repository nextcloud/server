<?php

namespace OpenStack\Test\Compute\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Test\TestCase;
use OpenStack\Compute\v2\Models\Host;
use OpenStack\Compute\v2\Api;

class HostTest extends TestCase
{
    /** @var Host */
    private $host;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->host = new Host($this->client->reveal(), new Api());
        $this->host->name = 'b6e4adbc193d428ea923899d07fb001e';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'os-hosts/b6e4adbc193d428ea923899d07fb001e', null, [], 'host-get');

        $this->host->retrieve();

        self::assertEquals("b6e4adbc193d428ea923899d07fb001e", $this->host->name);
    }
}
