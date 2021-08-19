<?php

namespace unit\Networking\v2\Extensions\SecurityGroups;

use GuzzleHttp\Psr7\Response;
use OpenStack\Test\TestCase;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Api;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroup;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Service;

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

    public function test_it_lists_secgroups()
    {
        $this->client
            ->request('GET', 'v2.0/security-groups', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('SecurityGroups'));

        foreach ($this->service->listSecurityGroups() as $sg) {
            /** @var $sg SecurityGroup */
            self::assertInstanceOf(SecurityGroup::class, $sg);

            self::assertEquals('default', $sg->name);
            self::assertEquals('default', $sg->description);
            self::assertEquals('85cc3048-abc3-43cc-89b3-377341426ac5', $sg->id);
            self::assertCount(2, $sg->securityGroupRules);
        }
    }

    public function test_it_creates_secgroup()
    {
        $options = [
            'name'        => 'new-webservers',
            'description' => 'security group for webservers',
        ];

        $expectedJson = ['security_group' => $options];

        $this->setupMock('POST', 'v2.0/security-groups', $expectedJson, [], new Response(201));

        $n = $this->service->createSecurityGroup($options);
        self::assertInstanceOf(SecurityGroup::class, $n);
    }

    public function test_it_gets_secgroup()
    {
        self::assertInstanceOf(SecurityGroup::class, $this->service->getSecurityGroup('id'));
    }

    public function test_it_lists_secgrouprules()
    {
        $this->client
            ->request('GET', 'v2.0/security-group-rules', ['headers' => []])
            ->shouldBeCalled()
            ->willReturn($this->getFixture('SecurityGroupRules'));

        foreach ($this->service->listSecurityGroupRules() as $sgr) {
            /** @var $sgr SecurityGroupRule */
            self::assertInstanceOf(SecurityGroupRule::class, $sgr);

            self::assertNotNull($sgr->direction);
            self::assertNotNull($sgr->ethertype);
            self::assertNotNull($sgr->id);
            self::assertNotNull($sgr->securityGroupId);
            self::assertNotNull($sgr->tenantId);
        }
    }

    public function test_it_creates_secgrouprule()
    {
        $options = [
            "direction"       => "ingress",
            "portRangeMin"    => "80",
            "ethertype"       => "IPv4",
            "portRangeMax"    => "80",
            "protocol"        => "tcp",
            "remoteGroupId"   => "85cc3048-abc3-43cc-89b3-377341426ac5",
            "securityGroupId" => "a7734e61-b545-452d-a3cd-0189cbd9747a",
        ];

        $expectedJson = ['security_group_rule' => [
            "direction"         => "ingress",
            "port_range_min"    => "80",
            "ethertype"         => "IPv4",
            "port_range_max"    => "80",
            "protocol"          => "tcp",
            "remote_group_id"   => "85cc3048-abc3-43cc-89b3-377341426ac5",
            "security_group_id" => "a7734e61-b545-452d-a3cd-0189cbd9747a",
        ]];

        $this->setupMock('POST', 'v2.0/security-group-rules', $expectedJson, [], new Response(201));

        $n = $this->service->createSecurityGroupRule($options);
        self::assertInstanceOf(SecurityGroupRule::class, $n);
    }

    public function test_it_gets_secgrouprule()
    {
        self::assertInstanceOf(SecurityGroupRule::class, $this->service->getSecurityGroupRule('id'));
    }
}
