<?php

namespace OpenStack\Test\ObjectStore\v1\Models;

use OpenStack\ObjectStore\v1\Api;
use OpenStack\ObjectStore\v1\Models\Account;
use OpenStack\Test\TestCase;

class AccountTest extends TestCase
{
    private $account;

    public function setUp(): void
    {
        parent::setUp();

        $this->rootFixturesDir = dirname(__DIR__);

        $this->account = new Account($this->client->reveal(), new Api());
    }

    public function test_Response_Populates_Model()
    {
        $response = $this->getFixture('HEAD_Account');

        $this->account->populateFromResponse($response);

        self::assertEquals(1, $this->account->objectCount);
        self::assertEquals(['Book' => 'MobyDick', 'Genre' => 'Fiction'], $this->account->metadata);
        self::assertEquals(14, $this->account->bytesUsed);
        self::assertEquals(2, $this->account->containerCount);
    }

    public function test_Retrieve()
    {
        $this->setupMock('HEAD', '', null, [], 'HEAD_Account');

        $this->account->retrieve();

        self::assertNotEmpty($this->account->metadata);
    }

    public function test_Get_Metadata()
    {
        $this->setupMock('HEAD', '', null, [], 'HEAD_Account');
        self::assertEquals(['Book' => 'MobyDick', 'Genre' => 'Fiction'], $this->account->getMetadata());
    }

    public function test_Merge_Metadata()
    {
        $headers = ['X-Account-Meta-Subject' => 'AmericanLiterature'];

        $this->setupMock('POST', '', [], $headers, 'NoContent');

        $this->account->mergeMetadata(['Subject' => 'AmericanLiterature']);
    }

    public function test_Reset_Metadata()
    {
        $this->setupMock('HEAD', '', null, [], 'HEAD_Account');

        $headers = [
            'X-Account-Meta-Book'         => 'Middlesex',
            'X-Account-Meta-Author'       => 'Jeffrey Eugenides',
            'X-Remove-Account-Meta-Genre' => 'True',
        ];

        $this->setupMock('POST', '', [], $headers, 'NoContent');

        $this->account->resetMetadata([
            'Book'   => 'Middlesex',
            'Author' => 'Jeffrey Eugenides',
        ]);
    }
}
