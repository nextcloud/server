<?php

namespace OpenStack\Test;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Prophecy\Argument;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var \Prophecy\Prophecy\ObjectProphecy */
    protected $client;

    /** @var string */
    protected $rootFixturesDir;

    protected $api;

    protected function setUp(): void
    {
        $this->client = $this->prophesize(ClientInterface::class);
    }

    protected function createResponse($status, array $headers, array $json)
    {
        return new Response($status, $headers, Utils::streamFor(json_encode($json)));
    }

    protected function getFixture($file)
    {
        if (!$this->rootFixturesDir) {
            throw new \RuntimeException('Root fixtures dir not set');
        }

        $path = $this->rootFixturesDir . '/Fixtures/' . $file . '.resp';

        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf("%s does not exist", $path));
        }

        return Message::parseResponse(file_get_contents($path));
    }

    protected function setupMock($method, $path, $body = null, array $headers = [], $response = null)
    {
        $options = ['headers' => $headers];

        if (!empty($body)) {
            $options[is_array($body) ? 'json' : 'body'] = $body;
        }

        if (is_string($response)) {
            $response = $this->getFixture($response);
        }

        $this->client
            ->request($method, $path, $options)
            ->shouldBeCalled()
            ->willReturn($response);
    }

    protected function createFn($receiver, $method, $args)
    {
        return function () use ($receiver, $method, $args) {
            return $receiver->$method($args);
        };
    }

    protected function listTest(callable $call, $urlPath, $modelName = null, $responseFile = null)
    {
        $modelName = $modelName ?: $urlPath;
        $responseFile = $responseFile ?: $urlPath;

        $this->setupMock('GET', $urlPath, null, [], $responseFile);

        $resources = call_user_func($call);

        self::assertInstanceOf('\Generator', $resources);

        $count = 0;

        foreach ($resources as $resource) {
            self::assertInstanceOf('OpenStack\Identity\v3\Models\\' . ucfirst($modelName), $resource);
            ++$count;
        }

        self::assertEquals(2, $count);
    }

    protected function getTest(callable $call, $modelName)
    {
        $resource = call_user_func($call);

        self::assertInstanceOf('OpenStack\Identity\v3\Models\\' . ucfirst($modelName), $resource);
        self::assertEquals('id', $resource->id);
    }
}
