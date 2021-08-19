<?php

namespace OpenStack\Test\Identity\v3\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Identity\v3\Api;
use OpenStack\Identity\v3\Models\Policy;
use OpenStack\Test\TestCase;

class PolicyTest extends TestCase
{
    private $policy;

    public function setUp(): void
    {
        $this->rootFixturesDir = dirname(__DIR__);

        parent::setUp();

        $this->policy = new Policy($this->client->reveal(), new Api());
        $this->policy->id = 'POLICY_ID';
    }

    public function test_it_creates()
    {
        $userOptions = [
            'blob' => 'blob',
            'projectId' => 'id',
            'type' => 'type',
            'userId' => 'id',
        ];

        $userJson = [
            'blob' => 'blob',
            'project_id' => 'id',
            'type' => 'type',
            'user_id' => 'id',
        ];

        $this->setupMock('POST', 'policies', ['policy' => $userJson], [], 'policy');

        /** @var $policy \OpenStack\Identity\v3\Models\Policy */
        $policy = $this->policy->create($userOptions);

        self::assertInstanceOf(Policy::class, $policy);
        self::assertEquals('--policy-id--', $policy->id);
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'policies/POLICY_ID', null, [], 'policy');

        $this->policy->retrieve();
    }

    public function test_it_updates()
    {
        $this->policy->type = 'foo';

        $this->setupMock('PATCH', 'policies/POLICY_ID', ['policy' => ['type' => 'foo']], [], 'policy');

        $this->policy->update();
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'policies/POLICY_ID', null, [], new Response(204));

        $this->policy->delete();
    }
}
