<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\OAuth2\Tests\Db;

use OCA\OAuth2\Db\Client;
use OCA\OAuth2\Db\ClientMapper;
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

	public function testGetByIdentifier() {
		$client = new Client();
		$client->setClientIdentifier('MyAwesomeClientIdentifier');
		$client->setName('Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('TotallyNotSecret');
		$this->clientMapper->insert($client);
		$client->resetUpdatedFields();
		$this->assertEquals($client, $this->clientMapper->getByIdentifier('MyAwesomeClientIdentifier'));
	}

	public function testGetByIdentifierNotExisting() {
		$this->expectException(\OCA\OAuth2\Exceptions\ClientNotFoundException::class);

		$this->clientMapper->getByIdentifier('MyTotallyNotExistingClient');
	}

	public function testGetByUid() {
		$client = new Client();
		$client->setClientIdentifier('MyNewClient');
		$client->setName('Client Name');
		$client->setRedirectUri('https://example.com/');
		$client->setSecret('TotallyNotSecret');
		$this->clientMapper->insert($client);
		$client->resetUpdatedFields();
		$this->assertEquals($client, $this->clientMapper->getByUid($client->getId()));
	}

	public function testGetByUidNotExisting() {
		$this->expectException(\OCA\OAuth2\Exceptions\ClientNotFoundException::class);

		$this->clientMapper->getByUid(1234);
	}

	public function testGetClients() {
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
