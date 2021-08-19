<?php

namespace OpenStack\Test\Subneting\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\Port;
use OpenStack\Networking\v2\Models\Quota;
use OpenStack\Test\TestCase;

class QuotaTest extends TestCase
{
    const TENANT_ID = 'aaaaaaa-bbbbbb-cccc-dddddd';

    /** @var Port */
    private $quota;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->quota = new Quota($this->client->reveal(), new Api());
        $this->quota->tenantId = self::TENANT_ID;
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/quotas/' . self::TENANT_ID, null, [], 'quota-get');

        $this->quota->retrieve();
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/quotas/' . self::TENANT_ID, null, [], new Response(204));

        $this->quota->delete();
    }

    public function test_it_updates()
    {
        $this->quota->subnet = 11;
        $this->quota->network = 22;
        $this->quota->floatingip = 33;
        $this->quota->subnetpool = 44;
        $this->quota->securityGroupRule = 55;
        $this->quota->securityGroup = 66;
        $this->quota->router = 77;
        $this->quota->rbacPolicy = 88;
        $this->quota->port = 99;

        $expectedJson = ['quota' => [
            'subnet'              => 11,
            'network'             => 22,
            'floatingip'          => 33,
            'subnetpool'          => 44,
            'security_group_rule' => 55,
            'security_group'      => 66,
            'router'              => 77,
            'rbac_policy'         => 88,
            'port'                => 99
        ]];

        $this->setupMock('PUT', 'v2.0/quotas/' . self::TENANT_ID, $expectedJson, [], 'quota-get');

        $this->quota->update();
    }
}
