<?php

namespace OpenStack\Test\Metric\v1\Gnocchi\Models;

use OpenStack\Metric\v1\Gnocchi\Models\Metric;
use OpenStack\Metric\v1\Gnocchi\Api;
use OpenStack\Test\TestCase;

class MetricTest extends TestCase
{
    /** @var Metric */
    private $metric;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->metric = new Metric($this->client->reveal(), new Api());
        $this->metric->id = '000b7bf8-0271-46dd-90aa-cfe89026a55a';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v1/metric/000b7bf8-0271-46dd-90aa-cfe89026a55a', null, [], 'metric-get');
        $this->metric->retrieve();

        self::assertEquals('000b7bf8-0271-46dd-90aa-cfe89026a55a', $this->metric->id);
        self::assertEquals('storage.objects.outgoing.bytes', $this->metric->name);
    }
}
