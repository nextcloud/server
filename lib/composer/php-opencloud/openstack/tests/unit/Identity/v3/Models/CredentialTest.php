<?php

namespace OpenStack\Test\Identity\v3\Models;

use GuzzleHttp\Psr7\Response;
use OpenStack\Identity\v3\Api;
use OpenStack\Identity\v3\Models\Credential;
use OpenStack\Test\TestCase;

class CredentialTest extends TestCase
{
    private $credential;

    public function setUp(): void
    {
        $this->rootFixturesDir = dirname(__DIR__);

        parent::setUp();

        $this->credential = new Credential($this->client->reveal(), new Api());
        $this->credential->id = 'CRED_ID';
    }

    public function test_it_retrieves()
    {
        $this->setupMock('GET', 'credentials/CRED_ID', null, [], 'cred');

        $this->credential->retrieve();
    }

    public function test_it_updates()
    {
        $this->credential->type = 'foo';
        $this->credential->projectId = 'bar';

        $expectedJson = [
            'type' => 'foo',
            'project_id' => 'bar',
        ];

        $this->setupMock('PATCH', 'credentials/CRED_ID', $expectedJson, [], 'cred');

        $this->credential->update();
    }

    public function test_it_deletes()
    {
        $this->setupMock('DELETE', 'credentials/CRED_ID', null, [], new Response(204));

        $this->credential->delete();
    }
}
