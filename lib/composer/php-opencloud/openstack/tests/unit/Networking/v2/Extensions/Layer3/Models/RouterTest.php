<?php

namespace unit\Networking\v2\Extensions\Layer3\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Test\TestCase;
use OpenStack\Networking\v2\Extensions\Layer3\Api;
use OpenStack\Networking\v2\Extensions\Layer3\Models\FixedIp;
use OpenStack\Networking\v2\Extensions\Layer3\Models\GatewayInfo;
use OpenStack\Networking\v2\Extensions\Layer3\Models\Router;

class RouterTest extends TestCase
{
    /** @var Router */
    private $router;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->router = new Router($this->client->reveal(), new Api());
        $this->router->id = 'id';
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/routers/id', null, [], new Response(202));

        $this->router->delete();
    }

    public function test_it_updates()
    {
        $expectedJson = ['router' => [
            'name'                => 'new',
            'external_gateway_info' => [
                "network_id"         => "8ca37218-28ff-41cb-9b10-039601ea7e6b",
                "enable_snat"        => true,
                "external_fixed_ips" => [
                    [
                        "subnet_id" => "255.255.255.0",
                        "ip"        => "192.168.10.1",
                    ],
                ],
            ],
        ]];

        $this->setupMock('PUT', 'v2.0/routers/id', $expectedJson, [], new Response(201));

        $gatewayInfo = new GatewayInfo();
        $gatewayInfo->networkId = '8ca37218-28ff-41cb-9b10-039601ea7e6b';
        $gatewayInfo->enableSnat = true;

        $fixedIp = new FixedIp();
        $fixedIp->subnetId = '255.255.255.0';
        $fixedIp->ip = '192.168.10.1';
        $gatewayInfo->fixedIps = [$fixedIp];

        $this->router->externalGatewayInfo = $gatewayInfo;
        $this->router->name = 'new';

        $this->router->update();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/routers/id', null, [], 'Router');

        $this->router->retrieve();

        self::assertEquals('f8a44de0-fc8e-45df-93c7-f79bf3b01c95', $this->router->id);
        self::assertCount(2, $this->router->externalGatewayInfo->fixedIps);
    }

    public function test_it_adds_interface()
    {
        $expectedJson = ['subnet_id' => 'a2f1f29d-571b-4533-907f-5803ab96ead1'];

        $this->setupMock('PUT', 'v2.0/routers/id/add_router_interface', $expectedJson, [], new Response(201));

        $this->router->addInterface(['subnetId' => 'a2f1f29d-571b-4533-907f-5803ab96ead1']);
    }

    public function test_it_remove_interface()
    {
        $expectedJson = ['subnet_id' => 'a2f1f29d-571b-4533-907f-5803ab96ead1'];

        $this->setupMock('PUT', 'v2.0/routers/id/remove_router_interface', $expectedJson, [], new Response(201));

        $this->router->removeInterface(['subnetId' => 'a2f1f29d-571b-4533-907f-5803ab96ead1']);
    }
}
