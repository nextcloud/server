<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Tests\Db;

use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Exceptions\ClientNotFoundException;
use Test\TestCase;

/**
 * @group DB
 */
class ClientMapperTest extends TestCase {
	/** @var ClientMapper */
	private $clientMapper;

	protected function setUp(): void {
		parent::setUp();
		$this->clientMapper = new ClientMapper(\OC::$server->getDatabaseConnection());
	}

	protected function tearDown(): void {
		$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$query->delete('oauth2_clients')->execute();

		parent::tearDown();
	}

	public function testGetByIdentifier(): void {
		$client = new Client();
		$client->setClientIdentifier('MyAwesomeClientIdentifier');
		$client->setName('Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('TotallyNotSecret');
		$this->clientMapper->insert($client);
		$client->resetUpdatedFields();
		$this->assertEquals($client, $this->clientMapper->getByIdentifier('MyAwesomeClientIdentifier'));
	}

	public function testGetByIdentifierNotExisting(): void {
		$this->expectException(ClientNotFoundException::class);

		$this->clientMapper->getByIdentifier('MyTotallyNotExistingClient');
	}

	public function testGetByUid(): void {
		$client = new Client();
		$client->setClientIdentifier('MyNewClient');
		$client->setName('Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('TotallyNotSecret');
		$this->clientMapper->insert($client);
		$client->resetUpdatedFields();
		$this->assertEquals($client, $this->clientMapper->getByUid($client->getId()));
	}

	public function testGetByUidNotExisting(): void {
		$this->expectException(ClientNotFoundException::class);

		$this->clientMapper->getByUid(1234);
	}

	public function testGetClients(): void {
		$this->assertSame('array', gettype($this->clientMapper->getClients()));
	}

	public function testInsertLongEncryptedSecret(): void {
		$client = new Client();
		$client->setClientIdentifier('MyNewClient');
		$client->setName('Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('b81dc8e2dc178817bf28ca7b37265aa96559ca02e6dcdeb74b42221d096ed5ef63681e836ae0ba1077b5fb5e6c2fa7748c78463f66fe0110c8dcb8dd7eb0305b16d0cd993e2ae275879994a2abf88c68|e466d9befa6b0102341458e45ecd551a|013af9e277374483123437f180a3b0371a411ad4f34c451547909769181a7d7cc191f0f5c2de78376d124dd7751b8c9660aabdd913f5e071fc6b819ba2e3d919|3');
		$this->clientMapper->insert($client);
		$this->assertTrue(true);
	}
}
