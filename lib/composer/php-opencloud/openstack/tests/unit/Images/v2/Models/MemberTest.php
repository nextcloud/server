<?php

namespace unit\Images\v2\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Images\v2\Api;
use OpenStack\Images\v2\Models\Member;
use OpenStack\Test\TestCase;

class MemberTest extends TestCase
{
    private $member;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->member = new Member($this->client->reveal(), new Api());
        $this->member->imageId = 'foo';
        $this->member->id = 'bar';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'v2/images/foo/members/bar', null, [], 'GET_member');

        $this->member->retrieve();
    }

    public function test_it_updates()
    {
        $expectedJson = ['status' => 'rejected'];

        $this->setupMock('PUT', 'v2/images/foo/members/bar', $expectedJson, [], 'GET_member');

        $this->member->updateStatus(Member::STATUS_REJECTED);
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'v2/images/foo/members/bar', null, [], new Response(204));

        $this->member->delete();
    }
}
