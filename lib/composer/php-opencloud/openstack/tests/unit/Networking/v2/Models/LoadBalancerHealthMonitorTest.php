<?php

namespace OpenStack\Test\Networking\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Networking\v2\Api;
use OpenStack\Networking\v2\Models\LoadBalancerHealthMonitor;
use OpenStack\Test\TestCase;

class LoadBalancerHealthMonitorTest extends TestCase
{
    private $healthmonitor;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->healthmonitor = new LoadBalancerHealthMonitor($this->client->reveal(), new Api());
        $this->healthmonitor->id = 'healthmonitorId';
    }

    public function test_it_creates()
    {
        $opts = [
            'adminStateUp'  => false,
            'tenantId'      => 'tenantId',
            'delay'         => 1,
            'type'          => 'HTTP',
            'expectedCodes' => '200',
            'maxRetries'    => 5,
            'httpMethod'    => 'GET',
            'urlPath'       => 'test',
            'timeout'       => 1
        ];

        $expectedJson = ['healthmonitor' => [
            'admin_state_up' => $opts['adminStateUp'],
            'tenant_id'      => $opts['tenantId'],
            'delay'          => $opts['delay'],
            'type'           => $opts['type'],
            'expected_codes' => $opts['expectedCodes'],
            'max_retries'    => $opts['maxRetries'],
            'http_method'    => $opts['httpMethod'],
            'url_path'       => $opts['urlPath'],
            'timeout'        => $opts['timeout']
        ]];

        $this->setupMock('POST', 'v2.0/lbaas/healthmonitors', $expectedJson, [], 'loadbalancer-healthmonitor-post');

        self::assertInstanceOf(LoadBalancerHealthMonitor::class, $this->healthmonitor->create($opts));
    }

    public function test_it_updates()
    {
        // Updatable attributes
        $this->healthmonitor->delay = 48;
        $this->healthmonitor->timeout = 54;
        $this->healthmonitor->maxRetries = 11;
        $this->healthmonitor->httpMethod = 'POST';
        $this->healthmonitor->urlPath = 'test2';
        $this->healthmonitor->expectedCodes = '200,201,202';
        $this->healthmonitor->adminStateUp = true;

        $expectedJson = ['healthmonitor' => [
            'delay'          => 48,
            'timeout'        => 54,
            'max_retries'    => 11,
            'http_method'    => 'POST',
            'url_path'       => 'test2',
            'expected_codes' => '200,201,202',
            'admin_state_up' => true
        ]];

        $this->setupMock('PUT', 'v2.0/lbaas/healthmonitors/healthmonitorId', $expectedJson, [], 'loadbalancer-healthmonitor-put');

        $this->healthmonitor->update();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/lbaas/healthmonitors/healthmonitorId', null, [], 'loadbalancer-healthmonitor-get');

        $this->healthmonitor->retrieve();

        self::assertEquals(1, $this->healthmonitor->delay);
        self::assertEquals(1, $this->healthmonitor->timeout);
        self::assertEquals('200', $this->healthmonitor->expectedCodes);
        self::assertEquals(5, $this->healthmonitor->maxRetries);
        self::assertEquals('GET', $this->healthmonitor->httpMethod);
        self::assertEquals('HTTP', $this->healthmonitor->type);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/lbaas/healthmonitors/healthmonitorId', null, [], new Response(204));

        $this->healthmonitor->delete();
    }
}
