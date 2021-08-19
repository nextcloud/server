<?php

namespace OpenStack\Test\Compute\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Compute\v2\Api;
use OpenStack\Compute\v2\Models\QuotaSet;
use OpenStack\Test\TestCase;

class QuotaSetTest extends TestCase
{
    /** @var  QuotaSet */
    private $quotaSet;

    const TENANT_ID = 'fake-tenant-id';

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->quotaSet = new QuotaSet($this->client->reveal(), new Api());
        $this->quotaSet->tenantId = self::TENANT_ID;
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'os-quota-sets/fake-tenant-id', null, [], 'quota-sets-get');

        $this->quotaSet->retrieve();

        self::assertEquals(10240, $this->quotaSet->injectedFileContentBytes);
        self::assertEquals(128, $this->quotaSet->metadataItems);
        self::assertEquals(22, $this->quotaSet->serverGroupMembers);
        self::assertEquals(10, $this->quotaSet->serverGroups);
        self::assertEquals(51200, $this->quotaSet->ram);
        self::assertEquals(33, $this->quotaSet->floatingIps);
        self::assertEquals(100, $this->quotaSet->keyPairs);
        self::assertEquals(999, $this->quotaSet->instances);
        self::assertEquals(20, $this->quotaSet->securityGroupRules);
        self::assertEquals(5, $this->quotaSet->injectedFiles);
        self::assertEquals(500, $this->quotaSet->cores);
        self::assertEquals(-1, $this->quotaSet->fixedIps);
        self::assertEquals(255, $this->quotaSet->injectedFilePathBytes);
        self::assertEquals(10, $this->quotaSet->securityGroups);

        self::assertEquals(self::TENANT_ID, $this->quotaSet->tenantId);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'os-quota-sets/fake-tenant-id', null, [], new Response(202));

        $this->quotaSet->delete();
    }

    public function test_it_updates()
    {
        $this->setupMock('PUT', 'os-quota-sets/fake-tenant-id', null, [], 'quota-sets-get');

        $this->quotaSet->update();
    }
}
