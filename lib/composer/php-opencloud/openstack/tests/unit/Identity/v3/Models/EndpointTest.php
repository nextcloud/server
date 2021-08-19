<?php

namespace OpenStack\Test\Identity\v3\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Identity\v3\Api;
use OpenStack\Identity\v3\Models\Endpoint;
use OpenStack\Identity\v3\Service;
use OpenStack\Test\TestCase;

class EndpointTest extends TestCase
{
    private $endpoint;
    private $service;

    public function setUp(): void
    {
        $this->rootFixturesDir = dirname(__DIR__);

        parent::setUp();

        $this->service  = new Service($this->client->reveal(), new Api());

        $this->endpoint = new Endpoint($this->client->reveal(), new Api());
        $this->endpoint->id = 'ENDPOINT_ID';
    }

    public function test_it_creates_endpoint()
    {
        $userOptions = [
            'interface' => 'admin',
            'name'      => 'name',
            'region'    => 'RegionOne',
            'url'       => 'foo.com',
            'serviceId' => '12345'
        ];

        $userJson = $userOptions;
        $userJson['service_id'] = $userOptions['serviceId'];
        unset($userJson['serviceId']);

        $this->setupMock('POST', 'endpoints', ['endpoint' => $userJson], [], 'endpoint');

        /** @var $endpoint \OpenStack\Identity\v3\Models\Endpoint */
        $endpoint = $this->service->createEndpoint($userOptions);

        self::assertInstanceOf(Endpoint::class, $endpoint);
    }

    public function test_it_updates_endpoint()
    {
        $this->endpoint->interface = 'admin';
        $this->endpoint->name = 'name';
        $this->endpoint->region = 'RegionOne';
        $this->endpoint->url = 'foo.com';
        $this->endpoint->serviceId = '12345';

        $userJson = [
            'interface'  => 'admin',
            'name'       => 'name',
            'region'     => 'RegionOne',
            'url'        => 'foo.com',
            'service_id' => '12345'
        ];

        $this->setupMock('PATCH', 'endpoints/ENDPOINT_ID', ['endpoint' => $userJson], [], 'endpoint');

        $this->endpoint->update();
    }

    public function test_it_deletes_endpoint()
    {
        $this->setupMock('DELETE', 'endpoints/ENDPOINT_ID', null, [], new Response(204));

        $this->endpoint->delete();
    }
}
