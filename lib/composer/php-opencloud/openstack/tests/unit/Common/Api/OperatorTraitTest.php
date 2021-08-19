<?php

namespace OpenStack\Test\Common\Api;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use OpenStack\Common\Api\Operation;
use OpenStack\Common\Api\OperatorTrait;
use OpenStack\Common\Resource\AbstractResource;
use OpenStack\Common\Resource\ResourceInterface;
use OpenStack\Test\Fixtures\ComputeV2Api;
use OpenStack\Test\TestCase;
use Prophecy\Argument;

class OperatorTraitTest extends TestCase
{
    /** @var TestOperator */
    private $operator;

    private $def;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = __DIR__;

        $this->def = [
            'method' => 'GET',
            'path'   => 'test',
            'params' => [],
        ];

        $this->operator = new TestOperator($this->client->reveal(), new ComputeV2Api());
    }

    public function test_it_returns_operations()
    {
        self::assertInstanceOf(
            Operation::class,
            $this->operator->getOperation($this->def, [])
        );
    }

    public function test_it_sends_a_request_when_operations_are_executed()
    {
        $this->client->request('GET', 'test', ['headers' => []])->willReturn(new Response());

        $this->operator->execute($this->def, []);

        $this->addToAssertionCount(1);
    }

    public function test_it_sends_a_request_when_async_operations_are_executed()
    {
        $this->client->requestAsync('GET', 'test', ['headers' => []])->willReturn(new Promise());

        $this->operator->executeAsync($this->def, []);

        $this->addToAssertionCount(1);
    }

    public function test_it_wraps_sequential_ops_in_promise_when_async_is_appended_to_method_name()
    {
        $promise = $this->operator->createAsync('something');

        self::assertInstanceOf(Promise::class, $promise);

        $promise->then(function ($val) {
            self::assertEquals('Created something', $val);
        });

        $promise->wait();
    }

    public function test_it_throws_exception_when_async_is_called_on_a_non_existent_method()
    {
		$this->expectException(\RuntimeException::class);
        $this->operator->fooAsync();
    }

    public function test_undefined_methods_result_in_error()
    {
		$this->expectException(\Exception::class);
        $this->operator->foo();
    }

    public function test_it_returns_a_model_instance()
    {
        self::assertInstanceOf(ResourceInterface::class, $this->operator->model(TestResource::class));
    }

    public function test_it_populates_models_from_response()
    {
        self::assertInstanceOf(ResourceInterface::class, $this->operator->model(TestResource::class, new Response(200)));
    }

    public function test_it_populates_models_from_arrays()
    {
        $data = ['flavor' => [], 'image' => []];
        self::assertInstanceOf(ResourceInterface::class, $this->operator->model(TestResource::class, $data));
    }

    public function test_guzzle_options_are_forwarded()
    {
        $this->client->request('GET', 'test', ['headers' => [], 'stream' => true])->willReturn(new Response());

        $this->operator->execute($this->def, [
            'requestOptions' => ['stream' => true]
        ]);

        $this->addToAssertionCount(1);
    }
}

class TestResource extends AbstractResource
{
}

class TestOperator
{
    use OperatorTrait;

    public function create($str)
    {
        return 'Created ' . $str;
    }

    public function fail()
    {
    }
}
