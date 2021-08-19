<?php

namespace OpenStack\Test\Networking\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\LoadBalancerStatus;
use OpenStack\Networking\v2\Models\LoadBalancerListener;
use OpenStack\Test\TestCase;

class LoadBalancerStatusTest extends TestCase
{
    private $status;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->status = new LoadBalancerStatus($this->client->reveal(), new Api());
        $this->status->loadbalancerId = 'loadbalancerId';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/lbaas/loadbalancers/loadbalancerId/statuses', null, [], 'loadbalancer-statuses-get');

        $this->status->retrieve();

        self::assertEquals('loadbalancer1', $this->status->name);
        self::assertEquals('loadbalancerId', $this->status->id);
        self::assertEquals('ONLINE', $this->status->operatingStatus);
        self::assertEquals('ACTIVE', $this->status->provisioningStatus);
        self::assertIsArray($this->status->listeners);
        self::assertArrayHasKey(0, $this->status->listeners);
        self::assertInstanceOf(LoadBalancerListener::class, $this->status->listeners[0]);
    }
}
