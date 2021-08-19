<?php

namespace OpenStack\Test\Subneting\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\Port;
use OpenStack\Test\TestCase;

class PortTest extends TestCase
{
    const NETWORK_ID = 'a87cc70a-3e15-4acf-8205-9b711a3531b7';
    const PORT_ID = 'a87cc70a-3e15-4acf-8205-9b711a3531b8';

    /** @var Port */
    private $port;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->port = new Port($this->client->reveal(), new Api());
        $this->port->id = self::PORT_ID;
        $this->port->networkId = self::NETWORK_ID;
    }

    public function test_it_updates()
    {
        $opts = [
            'name'         => 'newName',
            'networkId'    => self::NETWORK_ID,
            'adminStateUp' => false,
        ];

        $expectedJson = ['port' => [
            'name'           => $opts['name'],
            'network_id'     => $opts['networkId'],
            'admin_state_up' => $opts['adminStateUp'],
        ]];

        $this->setupMock('PUT', 'v2.0/ports/' . self::PORT_ID, $expectedJson, [], 'port_get');

        $this->port->adminStateUp = false;
        $this->port->name = 'newName';
        $this->port->update();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/ports/' . self::PORT_ID, null, [], 'port_get');

        $this->port->retrieve();
        self::assertEquals('46d4bfb9-b26e-41f3-bd2e-e6dcc1ccedb2', $this->port->id);
        self::assertEquals('ACTIVE', $this->port->status);
        self::assertEquals('port-name', $this->port->name);
        self::assertEquals(true, $this->port->adminStateUp);
        self::assertEquals(true,$this->port->portSecurityEnabled);
        self::assertEquals('network:router_interface', $this->port->deviceOwner);
        self::assertEquals('fake-device-id', $this->port->deviceId);
        self::assertEquals('00:11:22:33:44:55', $this->port->macAddress);
        self::assertCount(1, $this->port->fixedIps);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/ports/' . self::PORT_ID, null, [], new Response(204));

        $this->port->delete();
    }

    public function test_it_creates()
    {
        $opts = [
            'networkId' => self::NETWORK_ID,
            'fixedIps' => [
                [
                    'ipAddress' => '192.168.254.20',
                    'subnetId' => 'd8e52c33-b301-4feb-9856-a71b71f06c1d'
                ]
            ]
        ];

        $expectedJson = [
            'port' => [
                'network_id' => self::NETWORK_ID,
                'fixed_ips' => [
                    ['ip_address' => '192.168.254.20', 'subnet_id' => 'd8e52c33-b301-4feb-9856-a71b71f06c1d']
                ]
            ]
        ];

        $this->setupMock('POST', 'v2.0/ports', $expectedJson, [], 'port_post');

        $this->port->create($opts);
    }
}
