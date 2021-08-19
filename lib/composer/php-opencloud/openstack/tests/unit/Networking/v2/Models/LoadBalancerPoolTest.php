<?php

namespace OpenStack\Test\Networking\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\LoadBalancerPool;
use OpenStack\Networking\v2\Models\LoadBalancerMember;
use OpenStack\Networking\v2\Models\LoadBalancerHealthMonitor;
use OpenStack\Test\TestCase;

class LoadBalancerPoolTest extends TestCase
{
    private $pool;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->pool = new LoadBalancerPool($this->client->reveal(), new Api());
        $this->pool->id = 'poolId';
    }

    public function test_it_creates()
    {
        $opts = [
            'name'            => 'pool1',
            'description'     => 'simple pool',
            'protocol'        => 'HTTP',
            'lbAlgorithm'     => 'ROUND_ROBIN',
            'listenerId'      => 'listener1',
            'sessionPersistence' => [
              'type'        => 'APP_COOKIE',
              'cookie_name' => 'my_cookie'
            ],
            'adminStateUp'    => true
        ];

        $expectedJson = ['pool' => [
            'name'                => $opts['name'],
            'description'         => $opts['description'],
            'protocol'            => $opts['protocol'],
            'lb_algorithm'        => $opts['lbAlgorithm'],
            'listener_id'         => $opts['listenerId'],
            'session_persistence' => $opts['sessionPersistence'],
            'admin_state_up'      => $opts['adminStateUp']
        ]];

        $this->setupMock('POST', 'v2.0/lbaas/pools', $expectedJson, [], 'loadbalancer-pool-post');

        self::assertInstanceOf(LoadBalancerPool::class, $this->pool->create($opts));
    }

    public function test_it_updates()
    {
        // Updatable attributes
        $this->pool->name = 'foo';
        $this->pool->description = 'bar';
        $this->pool->lbAlgorithm = 'LEAST_CONNECTIONS';
        $this->pool->sessionPersistence = [
            'type'        => 'APP_COOKIE',
            'cookie_name' => 'new_cookie'
        ];
        $this->pool->adminStateUp = false;

        $expectedJson = ['pool' => [
            'name'                => 'foo',
            'description'         => 'bar',
            'lb_algorithm'        => 'LEAST_CONNECTIONS',
            'session_persistence' => [
                'type'        => 'APP_COOKIE',
                'cookie_name' => 'new_cookie'
            ],
            'admin_state_up'      => false
        ]];

        $this->setupMock('PUT', 'v2.0/lbaas/pools/poolId', $expectedJson, [], 'loadbalancer-pool-put');

        $this->pool->update();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/lbaas/pools/poolId', null, [], 'loadbalancer-pool-get');

        $this->pool->retrieve();

        self::assertEquals('poolId', $this->pool->id);
        self::assertEquals('pool1', $this->pool->name);
        self::assertEquals('simple pool', $this->pool->description);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/lbaas/pools/poolId', null, [], new Response(204));

        $this->pool->delete();
    }

    public function test_add_member()
    {
        $opts = [
            'address'      => '10.0.0.8',
            'protocolPort' => 80,
            'subnetId'     => 'subnetId',
            'adminStateUp' => true,
            'weight'       => 1
        ];

        $expectedJson = ['member' => [
            'address'        => $opts['address'],
            'protocol_port'  => $opts['protocolPort'],
            'subnet_id'      => $opts['subnetId'],
            'admin_state_up' => $opts['adminStateUp'],
            'weight'         => $opts['weight']
        ]];

        $this->setupMock('POST', 'v2.0/lbaas/pools/poolId/members', $expectedJson, [], 'loadbalancer-member-post');

        self::assertInstanceOf(LoadBalancerMember::class, $this->pool->addMember($opts));
    }

    public function test_get_member()
    {
        $memberId = 'memberId';

        self::assertInstanceOf(LoadBalancerMember::class, $this->pool->getMember($memberId));
    }

    public function test_delete_member()
    {
        $this->setupMock('DELETE', 'v2.0/lbaas/pools/poolId/members/memberId', null, [], new Response(204));

        $this->pool->deleteMember('memberId');
    }

    public function test_add_health_monitor()
    {
        $opts = [
            'adminStateUp'  => false,
            'tenantId'      => 'tenantId',
            'delay'         => 1,
            'type'          => 'HTTP',
            'expectedCodes' => '200',
            'maxRetries'    => 5,
            'httpMethod'    => 'GET',
            'urlPath'       => 'test',
            'timeout'       => 1
        ];

        $expectedJson = ['healthmonitor' => [
            'admin_state_up' => $opts['adminStateUp'],
            'tenant_id'      => $opts['tenantId'],
            'delay'          => $opts['delay'],
            'type'           => $opts['type'],
            'expected_codes' => $opts['expectedCodes'],
            'max_retries'    => $opts['maxRetries'],
            'http_method'    => $opts['httpMethod'],
            'url_path'       => $opts['urlPath'],
            'timeout'        => $opts['timeout'],
            'pool_id'        => $this->pool->id
        ]];

        $this->setupMock('POST', 'v2.0/lbaas/healthmonitors', $expectedJson, [], 'loadbalancer-healthmonitor-post');

        self::assertInstanceOf(LoadBalancerHealthMonitor::class, $this->pool->addHealthMonitor($opts));
    }
}
