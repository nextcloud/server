<?php

namespace OpenStack\Test\Compute\v2\Models;

use OpenStack\Compute\v2\Api;
use OpenStack\Compute\v2\Models\Hypervisor;
use OpenStack\Test\TestCase;

class HypervisorTest extends TestCase
{
    /**@var Hypervisor */
    private $hypervisor;

    const ID = 1;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->hypervisor = new Hypervisor($this->client->reveal(), new Api());
        $this->hypervisor->id = self::ID;
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'os-hypervisors/' . self::ID, null, [], 'hypervisor-get');

        $this->hypervisor->retrieve();

        self::assertEquals('1', $this->hypervisor->id);
        self::assertEquals('enabled', $this->hypervisor->status);
        self::assertEquals('up', $this->hypervisor->state);
        self::assertEquals('146', $this->hypervisor->freeDiskGb);
        self::assertEquals('76917', $this->hypervisor->freeRamMb);
        self::assertEquals('localhost.localdomain', $this->hypervisor->hypervisorHostname);
        self::assertEquals('QEMU', $this->hypervisor->hypervisorType);
        self::assertEquals('2006000', $this->hypervisor->hypervisorVersion);
        self::assertEquals('266', $this->hypervisor->localGb);
        self::assertEquals('120', $this->hypervisor->localGbUsed);
        self::assertEquals('97909', $this->hypervisor->memoryMb);
        self::assertEquals('20992', $this->hypervisor->memoryMbUsed);
        self::assertEquals('4', $this->hypervisor->runningVms);
        self::assertEquals('56', $this->hypervisor->vcpus);
        self::assertEquals('10', $this->hypervisor->vcpusUsed);
        self::assertEquals(['host' => 'localhost.localdomain', 'id' => '8', 'disabled_reason' => null], $this->hypervisor->service);
    }
}
