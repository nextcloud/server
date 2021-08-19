<?php

namespace OpenStack\Test\Networking\v2\Extensions\Layer3;

use GuzzleHttp\Psr7\Response;
use OpenStack\Test\TestCase;
use OpenStack\Networking\v2\Extensions\Layer3\Api;
use OpenStack\Networking\v2\Extensions\Layer3\Models\FloatingIp;
use OpenStack\Networking\v2\Extensions\Layer3\Models\Router;
use OpenStack\Networking\v2\Extensions\Layer3\Service;

class ServiceTest extends TestCase
{
    /** @var Service */
    private $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = __DIR__;

        $this->service = new Service($this->client->reveal(), new Api());
    }

    public function test_it_lists_floating_ips()
    {
        $this->client
            ->request('GET', 'v2.0/floatingips', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('FloatingIps'));

        foreach ($this->service->listFloatingIps() as $ip) {
            /** @var $ip FloatingIp */
            self::assertInstanceOf(FloatingIp::class, $ip);

            self::assertNotNull($ip->tenantId);
            self::assertNotNull($ip->floatingNetworkId);
            self::assertNotNull($ip->floatingIpAddress);
            self::assertNotNull($ip->id);
            self::assertNotNull($ip->status);
        }
    }

    public function test_it_gets_floating_ip()
    {
        self::assertInstanceOf(FloatingIp::class, $this->service->getFloatingIp('id'));
    }

    public function test_it_creates_floatingIp()
    {
        $expectedJson = ["floatingip" => [
            "floating_network_id" => "376da547-b977-4cfe-9cba-275c80debf57",
            "port_id"             => "ce705c24-c1ef-408a-bda3-7bbd946164ab",
        ]];

        $this->setupMock('POST', 'v2.0/floatingips', $expectedJson, [], new Response(201));

        $ip = $this->service->createFloatingIp([
            "floatingNetworkId" => "376da547-b977-4cfe-9cba-275c80debf57",
            "portId"            => "ce705c24-c1ef-408a-bda3-7bbd946164ab",
        ]);

        self::assertInstanceOf(FloatingIp::class, $ip);
    }

    public function test_it_lists_routers()
    {
        $this->client
            ->request('GET', 'v2.0/routers', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('Routers'));

        foreach ($this->service->listRouters() as $r) {
            /** @var $r Router */
            self::assertInstanceOf(Router::class, $r);

            self::assertNotNull($r->status);
            self::assertNotNull($r->name);
            self::assertNotNull($r->adminStateUp);
            self::assertNotNull($r->tenantId);
            self::assertNotNull($r->id);
        }
    }

    public function test_it_gets_router()
    {
        self::assertInstanceOf(Router::class, $this->service->getRouter('id'));
    }

    public function test_it_creates_router()
    {
        $expectedJson = ["router" => [
            "name"                  => "test_router",
            "external_gateway_info" => [
                "network_id"         => "8ca37218-28ff-41cb-9b10-039601ea7e6b",
                "enable_snat"        => true,
                "external_fixed_ips" => [
                    [
                        "subnet_id" => "255.255.255.0",
                        "ip"        => "192.168.10.1",
                    ],
                ],
            ],
            "admin_state_up"        => true,
        ]];

        $this->setupMock('POST', 'v2.0/routers', $expectedJson, [], new Response(201));

        $r = $this->service->createRouter([
            'name'                => 'test_router',
            'adminStateUp'        => true,
            'externalGatewayInfo' => [
                'networkId'  => '8ca37218-28ff-41cb-9b10-039601ea7e6b',
                'enableSnat' => true,
                'fixedIps'   => [
                    [
                        'subnetId' => '255.255.255.0',
                        'ip'       => '192.168.10.1',
                    ],
                ],
            ],
        ]);

        self::assertInstanceOf(Router::class, $r);
    }
}
