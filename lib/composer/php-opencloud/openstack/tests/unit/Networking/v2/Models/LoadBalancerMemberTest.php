<?php

namespace OpenStack\Test\Networking\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\LoadBalancerMember;
use OpenStack\Test\TestCase;

class LoadBalancerMemberTest extends TestCase
{
    private $member;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->member = new LoadBalancerMember($this->client->reveal(), new Api());
        $this->member->poolId = 'poolId';
        $this->member->id = 'memberId';
    }

    public function test_it_creates()
    {
        $opts = [
            'address'      => '127.0.0.1',
            'protocolPort' => 443,
            'weight'       => 42,
            'subnetId'     => 'subnetId',
            'adminStateUp' => true
        ];

        $expectedJson = ['member' => [
            'address'        => $opts['address'],
            'protocol_port'  => $opts['protocolPort'],
            'weight'         => $opts['weight'],
            'subnet_id'      => $opts['subnetId'],
            'admin_state_up' => $opts['adminStateUp']
        ]];

        $this->setupMock('POST', 'v2.0/lbaas/pools/poolId/members', $expectedJson, [], 'loadbalancer-member-post');

        self::assertInstanceOf(LoadBalancerMember::class, $this->member->create($opts));
    }

    public function test_it_updates()
    {
        // Updatable attributes
        $this->member->weight = 154;
        $this->member->adminStateUp = false;

        $expectedJson = ['member' => [
            'weight'         => 154,
            'admin_state_up' => false
        ]];

        $this->setupMock('PUT', 'v2.0/lbaas/pools/poolId/members/memberId', $expectedJson, [], 'loadbalancer-member-put');

        $this->member->update();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/lbaas/pools/poolId/members/memberId', null, [], 'loadbalancer-member-get');

        $this->member->retrieve();

        self::assertEquals('memberId', $this->member->id);
        self::assertEquals(1, $this->member->weight);
        self::assertEquals(true, $this->member->adminStateUp);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/lbaas/pools/poolId/members/memberId', null, [], new Response(204));

        $this->member->delete();
    }
}
