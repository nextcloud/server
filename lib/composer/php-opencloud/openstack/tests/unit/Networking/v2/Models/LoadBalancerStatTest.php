<?php

namespace OpenStack\Test\Networking\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\LoadBalancerStat;
use OpenStack\Test\TestCase;

class LoadBalancerStatTest extends TestCase
{
    private $stat;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->stat = new LoadBalancerStat($this->client->reveal(), new Api());
        $this->stat->loadbalancerId = 'loadbalancerId';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/lbaas/loadbalancers/loadbalancerId/stats', null, [], 'loadbalancer-stats-get');

        $this->stat->retrieve();
        self::assertEquals('1234', $this->stat->bytesOut);
        self::assertEquals('4321', $this->stat->bytesIn);
        self::assertEquals(25, $this->stat->totalConnections);
        self::assertEquals(10, $this->stat->activeConnections);
    }
}
