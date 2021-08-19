<?php

namespace OpenStack\Test\Common\Resource;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\ResourceInterface;
use OpenStack\Test\Common\Service\Fixtures\Api;
use OpenStack\Test\Common\Service\Fixtures\Models\Foo;
use OpenStack\Test\Common\Service\Fixtures\Service;
use OpenStack\Test\Fixtures\ComputeV2Api;
use OpenStack\Test\TestCase;

class OperatorResourceTest extends TestCase
{
    /** @var TestOperatorResource */
    private $resource;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = __DIR__;

        $this->resource = new TestOperatorResource($this->client->reveal(), new Api());
    }

    public function test_it_retrieves_base_http_url()
    {
        $returnedUri = \GuzzleHttp\Psr7\uri_for('http://foo.com');
        $this->client->getConfig('base_uri')->shouldBeCalled()->willReturn($returnedUri);

        $uri = $this->resource->testBaseUri();

        self::assertInstanceOf(Uri::class, $uri);
        self::assertEquals($returnedUri, $uri);
    }

    public function test_it_executes_with_state()
    {
        $this->resource->id  = 'foo';
        $this->resource->bar = 'bar';

        $expectedJson = ['id' => 'foo', 'bar' => 'bar'];

        $this->setupMock('GET', 'foo', $expectedJson, [], new Response(204));

        $this->resource->executeWithState((new ComputeV2Api())->test());
    }

    public function test_it_executes_operations_until_a_204_is_received()
    {
        $this->client
            ->request('GET', 'servers', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('servers-page1'));

        $this->client
            ->request('GET', 'servers', ['query' => ['marker' => '5'], 'headers' => []])
            ->shouldBeCalled()
            ->willReturn(new Response(204));

        $count = 0;

        $api = new ComputeV2Api();

        foreach ($this->resource->enumerate($api->getServers()) as $item) {
            ++$count;
            self::assertInstanceOf(TestOperatorResource::class, $item);
        }

        self::assertEquals(5, $count);
    }

    public function test_it_invokes_function_if_provided()
    {
        $this->client
            ->request('GET', 'servers', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('servers-page1'));

        $this->client
            ->request('GET', 'servers', ['query' => ['marker' => '5'], 'headers' => []])
            ->shouldBeCalled()
            ->willReturn(new Response(204));

        $api = new ComputeV2Api();

        $count = 0;

        $fn = function () use (&$count) {
            ++$count;
        };

        foreach ($this->resource->enumerate($api->getServers(), [], $fn) as $item) {
        }

        self::assertEquals(5, $count);
    }

    public function test_it_halts_when_user_provided_limit_is_reached()
    {
        $this->client
            ->request('GET', 'servers', ['query' => ['limit' => 2], 'headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('servers-page1'));

        $count = 0;

        $api = new ComputeV2Api();

        foreach ($this->resource->enumerate($api->getServers(), ['limit' => 2]) as $item) {
            ++$count;
        }

        self::assertEquals(2, $count);
    }

    public function test_it_predicts_resources_key_without_explicit_property()
    {
        $this->client
            ->request('GET', 'servers', ['query' => ['limit' => 2], 'headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('servers-page1'));

        $count = 0;

        $api      = new ComputeV2Api();
        $resource = new Server($this->client->reveal(), new $api());

        foreach ($resource->enumerate($api->getServers(), ['limit' => 2]) as $item) {
            ++$count;
        }

        self::assertEquals(2, $count);
    }

    public function test_it_extracts_multiple_instances()
    {
        $response = $this->getFixture('servers-page1');
        $resource = new Server($this->client->reveal(), new Api());

        $resources = $resource->extractMultipleInstances($response);

        foreach ($resources as $resource) {
            self::assertInstanceOf(Server::class, $resource);
        }
    }

    public function test_it_finds_parent_service()
    {
        $r = new Foo($this->client->reveal(), new Api());
        self::assertInstanceOf(Service::class, $r->testGetService());
    }

    public function test_it_returns_a_model_instance()
    {
        self::assertInstanceOf(ResourceInterface::class, $this->resource->model(TestResource::class));
    }

    public function test_it_populates_models_from_response()
    {
        self::assertInstanceOf(ResourceInterface::class, $this->resource->model(TestResource::class, new Response(200)));
    }

    public function test_it_populates_models_from_arrays()
    {
        $data = [
            'id'  => 123,
            'bar' => 'this-is-bar',
        ];

        /** @var TestOperatorResource $model */
        $model = $this->resource->model(TestOperatorResource::class, $data);

        self::assertInstanceOf(ResourceInterface::class, $model);
        self::assertEquals(123, $model->id);
        self::assertEquals('this-is-bar', $model->bar);
    }
}

class TestOperatorResource extends OperatorResource
{
    protected $resourceKey  = 'foo';
    protected $resourcesKey = 'servers';
    protected $markerKey    = 'id';

    /** @var string */
    public $bar;

    public $id;

    /** @var \DateTimeImmutable */
    public $created;

    /** @var []TestResource */
    public $children;

    public function testBaseUri()
    {
        return $this->getHttpBaseUrl();
    }
}

class Server extends OperatorResource
{
}
