<?php

namespace OpenStack\Test\Common\Service;

use GuzzleHttp\Client;
use OpenStack\Common\Auth\IdentityService;
use OpenStack\Common\Auth\Token;
use OpenStack\Common\Service\Builder;
use OpenStack\Test\Common\Service\Fixtures;
use OpenStack\Test\TestCase;
use Prophecy\Argument;

class BuilderTest extends TestCase
{
    private $builder;
    private $opts;

    public function setUp(): void
    {
        $this->builder = new Builder([]);

        $this->opts = [
            'username'    => '1',
            'password'    => '2',
            'tenantId'    => '3',
            'authUrl'     => '4',
            'region'      => '5',
            'catalogName' => '6',
            'catalogType' => '7',
        ];
    }

    public function test_it_throws_exception_if_username_is_missing()
    {
		$this->expectException(\Exception::class);
        $this->builder->createService('Compute\\v2', []);
    }

    public function test_it_throws_exception_if_password_is_missing()
    {
		$this->expectException(\Exception::class);
        $this->builder->createService('Compute\\v2', ['username' => 1]);
    }

    public function test_it_throws_exception_if_both_tenantId_and_tenantName_is_missing()
    {
		$this->expectException(\Throwable::class);
        $this->builder->createService('Compute\\v2', [
            'username' => 1, 'password' => 2, 'authUrl' => 4, 'region' => 5, 'catalogName' => 6, 'catalogType' => 7,
        ]);
    }

    public function test_it_throws_exception_if_authUrl_is_missing()
    {
		$this->expectException(\Throwable::class);
        $this->builder->createService('Compute\\v2', ['username' => 1, 'password' => 2, 'tenantId' => 3]);
    }

    public function test_it_throws_exception_if_region_is_missing()
    {
		$this->expectException(\Throwable::class);

        $this->builder->createService('Compute\\v2', [
            'username' => 1, 'password' => 2, 'tenantId' => 3, 'authUrl' => 4,
        ]);
    }

    public function test_it_throws_exception_if_catalogName_is_missing()
    {
		$this->expectException(\Throwable::class);

        $this->builder->createService('Compute\\v2', [
            'username' => 1, 'password' => 2, 'tenantId' => 3, 'authUrl' => 4,
        ]);
    }

    public function test_it_throws_exception_if_catalogType_is_missing()
    {
		$this->expectException(\Throwable::class);

        $this->builder->createService('Compute\\v2', [
            'username' => 1, 'password' => 2, 'tenantId' => 3, 'authUrl' => 4, 'region' => 5, 'catalogName' => 6,
        ]);
    }

    public function test_it_creates_service()
    {
        $is = $this->prophesize(TestIdentity::class);
        $is->authenticate(Argument::any())->willReturn([new FakeToken(), '']);

        $s = $this->builder->createService('Test\\Common\\Service\\Fixtures', $this->opts + [
            'identityService' => $is->reveal(),
        ]);

        self::assertInstanceOf(Fixtures\Service::class, $s);
    }

    public function test_it_does_not_authenticate_for_identity_services()
    {
        $is = $this->prophesize(TestIdentity::class);
        $is->authenticate(Argument::any())->willReturn([new FakeToken(), '']);

        $s = $this->builder->createService('Test\\Common\\Service\\Fixtures\\Identity', $this->opts + [
            'identityService' => $is->reveal(),
        ]);

        self::assertInstanceOf(Fixtures\Identity\Service::class, $s);
    }

    public function test_it_create_service_with_micro_version()
    {
        $is = $this->prophesize(TestIdentity::class);
        $is->authenticate(Argument::any())->willReturn([new FakeToken(), '']);

        $s = $this->builder->createService('Test\\Common\\Service\\Fixtures', $this->opts + [
                'identityService' => $is->reveal(),
                'microVersion' => '1.2.3'
            ]);

        self::assertInstanceOf(Fixtures\Service::class, $s);

        $refClass = new \ReflectionClass($s);
        $refProperty = $refClass->getProperty('client');
        $refProperty->setAccessible(true);

        /** @var Client $client */
        $client = $refProperty->getValue($s);

        $headers = $client->getConfig()['headers'];
        self::assertArrayHasKey('OpenStack-API-Version', $headers);
        self::assertEquals('7 1.2.3', $headers['OpenStack-API-Version']);
    }
}

class FakeToken implements Token
{
    public function getId(): string
    {
        return '';
    }

    public function hasExpired(): bool
    {
        return false;
    }
}

class TestIdentity implements IdentityService
{
    public function authenticate(array $options): array
    {
        return [];
    }
}
