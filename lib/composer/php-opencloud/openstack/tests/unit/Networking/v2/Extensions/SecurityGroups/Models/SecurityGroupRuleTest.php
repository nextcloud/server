<?php declare(strict_types=1);

namespace OpenStack\Test\Networking\v2\Extensions\SecurityGroups\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Test\TestCase;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Api;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule;

class SecurityGroupRuleTest extends TestCase
{
    /** @var SecurityGroupRule */
    private $securityGroupRule;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->securityGroupRule = new SecurityGroupRule($this->client->reveal(), new Api());
        $this->securityGroupRule->id = 'id';
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2.0/security-group-rules/id', null, [], new Response(202));

        $this->securityGroupRule->delete();
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2.0/security-group-rules/id', null, [], 'SecurityGroupRule');

        $this->securityGroupRule->retrieve();
    }
}
