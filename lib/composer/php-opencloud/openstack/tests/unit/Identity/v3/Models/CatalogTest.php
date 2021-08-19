<?php

namespace OpenStack\Test\Identity\v3\Models;

use OpenStack\Identity\v3\Api;
use OpenStack\Identity\v3\Models\Catalog;
use OpenStack\Identity\v3\Models\Service;
use OpenStack\Test\TestCase;
use Prophecy\Argument;

class CatalogTest extends TestCase
{
    private $catalog;

    public function setUp(): void
    {
        $this->rootFixturesDir = dirname(__DIR__);

        parent::setUp();

        $this->catalog = new Catalog($this->client->reveal(), new Api());
    }

    public function test_it_throws_if_no_services_set()
    {
		$this->expectException(\RuntimeException::class);
        self::assertFalse($this->catalog->getServiceUrl('', '', '', ''));
    }

    public function test_it_returns_service_url()
    {
        $url = 'http://example.org';

        $service = $this->prophesize(Service::class);
        $service->getUrl('foo', 'bar', 'baz', '')->shouldBeCalled()->willReturn($url);

        $this->catalog->services = [$service->reveal()];

        self::assertEquals($url, $this->catalog->getServiceUrl('foo', 'bar', 'baz', ''));
    }

    public function test_it_throws_if_no_url_found()
    {
        $service = $this->prophesize(Service::class);
        $service->getUrl(Argument::any(), Argument::cetera())->shouldBeCalled()->willReturn(false);
		$this->expectException(\RuntimeException::class);

        $this->catalog->services = [$service->reveal()];

        self::assertFalse($this->catalog->getServiceUrl('', '', '', ''));
    }
}
