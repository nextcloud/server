<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Federation\Tests;

use OCA\Federation\DbHandler;
use OCA\Federation\TrustedServers;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class DbHandlerTest extends TestCase {
	private DbHandler $dbHandler;
	private IL10N&MockObject $il10n;
	private IDBConnection $connection;
	private string $dbTable = 'trusted_servers';

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->il10n = $this->createMock(IL10N::class);

		$this->dbHandler = new DbHandler(
			$this->connection,
			$this->il10n
		);

		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertEmpty($result, 'we need to start with a empty trusted_servers table');
	}

	protected function tearDown(): void {
		$query = $this->connection->getQueryBuilder()->delete($this->dbTable);
		$query->executeStatement()
		;
		parent::tearDown();
	}

	/**
	 * @dataProvider dataTestAddServer
	 *
	 * @param string $url passed to the method
	 * @param string $expectedUrl the url we expect to be written to the db
	 * @param string $expectedHash the hash value we expect to be written to the db
	 */
	public function testAddServer(string $url, string $expectedUrl, string $expectedHash): void {
		$id = $this->dbHandler->addServer($url);

		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame($expectedUrl, $result[0]['url']);
		$this->assertSame($id, (int)$result[0]['id']);
		$this->assertSame($expectedHash, $result[0]['url_hash']);
		$this->assertSame(TrustedServers::STATUS_PENDING, (int)$result[0]['status']);
	}

	public static function dataTestAddServer(): array {
		return [
			['http://owncloud.org', 'http://owncloud.org', sha1('owncloud.org')],
			['https://owncloud.org', 'https://owncloud.org', sha1('owncloud.org')],
			['http://owncloud.org/', 'http://owncloud.org', sha1('owncloud.org')],
		];
	}

	public function testRemove(): void {
		$id1 = $this->dbHandler->addServer('server1');
		$id2 = $this->dbHandler->addServer('server2');

		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(2, $result);
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame('server2', $result[1]['url']);
		$this->assertSame($id1, (int)$result[0]['id']);
		$this->assertSame($id2, (int)$result[1]['id']);

		$this->dbHandler->removeServer($id2);
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame($id1, (int)$result[0]['id']);
	}


	public function testGetServerById(): void {
		$this->dbHandler->addServer('server1');
		$id = $this->dbHandler->addServer('server2');

		$result = $this->dbHandler->getServerById($id);
		$this->assertSame('server2', $result['url']);
	}

	public function testGetAll(): void {
		$id1 = $this->dbHandler->addServer('server1');
		$id2 = $this->dbHandler->addServer('server2');

		$result = $this->dbHandler->getAllServer();
		$this->assertSame(2, count($result));
		$this->assertSame('server1', $result[0]['url']);
		$this->assertSame('server2', $result[1]['url']);
		$this->assertSame($id1, (int)$result[0]['id']);
		$this->assertSame($id2, (int)$result[1]['id']);
	}

	/**
	 * @dataProvider dataTestServerExists
	 */
	public function testServerExists(string $serverInTable, string $checkForServer, bool $expected): void {
		$this->dbHandler->addServer($serverInTable);
		$this->assertSame($expected,
			$this->dbHandler->serverExists($checkForServer)
		);
	}

	public static function dataTestServerExists(): array {
		return [
			['server1', 'server1', true],
			['server1', 'http://server1', true],
			['server1', 'server2', false]
		];
	}

	public function XtestAddToken() {
		$this->dbHandler->addServer('server1');
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame(null, $result[0]['token']);
		$this->dbHandler->addToken('http://server1', 'token');
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame('token', $result[0]['token']);
	}

	public function testGetToken(): void {
		$this->dbHandler->addServer('server1');
		$this->dbHandler->addToken('http://server1', 'token');
		$this->assertSame('token',
			$this->dbHandler->getToken('https://server1')
		);
	}

	public function XtestAddSharedSecret() {
		$this->dbHandler->addServer('server1');
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame(null, $result[0]['shared_secret']);
		$this->dbHandler->addSharedSecret('http://server1', 'secret');
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->execute();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame('secret', $result[0]['shared_secret']);
	}

	public function testGetSharedSecret(): void {
		$this->dbHandler->addServer('server1');
		$this->dbHandler->addSharedSecret('http://server1', 'secret');
		$this->assertSame('secret',
			$this->dbHandler->getSharedSecret('https://server1')
		);
	}

	public function testSetServerStatus(): void {
		$this->dbHandler->addServer('server1');
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame(TrustedServers::STATUS_PENDING, (int)$result[0]['status']);
		$this->dbHandler->setServerStatus('http://server1', TrustedServers::STATUS_OK);
		$query = $this->connection->getQueryBuilder()->select('*')->from($this->dbTable);

		$qResult = $query->executeQuery();
		$result = $qResult->fetchAll();
		$qResult->closeCursor();
		$this->assertCount(1, $result);
		$this->assertSame(TrustedServers::STATUS_OK, (int)$result[0]['status']);
	}

	public function testGetServerStatus(): void {
		$this->dbHandler->addServer('server1');
		$this->dbHandler->setServerStatus('http://server1', TrustedServers::STATUS_OK);
		$this->assertSame(TrustedServers::STATUS_OK,
			$this->dbHandler->getServerStatus('https://server1')
		);

		// test sync token
		$this->dbHandler->setServerStatus('http://server1', TrustedServers::STATUS_OK, 'token1234567890');
		$servers = $this->dbHandler->getAllServer();
		$this->assertSame('token1234567890', $servers[0]['sync_token']);
	}

	/**
	 * hash should always be computed with the normalized URL
	 *
	 * @dataProvider dataTestHash
	 */
	public function testHash(string $url, string $expected): void {
		$this->assertSame($expected,
			$this->invokePrivate($this->dbHandler, 'hash', [$url])
		);
	}

	public static function dataTestHash(): array {
		return [
			['server1', sha1('server1')],
			['http://server1', sha1('server1')],
			['https://server1', sha1('server1')],
			['http://server1/', sha1('server1')],
		];
	}

	/**
	 * @dataProvider dataTestNormalizeUrl
	 */
	public function testNormalizeUrl(string $url, string $expected): void {
		$this->assertSame($expected,
			$this->invokePrivate($this->dbHandler, 'normalizeUrl', [$url])
		);
	}

	public static function dataTestNormalizeUrl(): array {
		return [
			['owncloud.org', 'owncloud.org'],
			['http://owncloud.org', 'owncloud.org'],
			['https://owncloud.org', 'owncloud.org'],
			['https://owncloud.org//mycloud', 'owncloud.org/mycloud'],
			['https://owncloud.org/mycloud/', 'owncloud.org/mycloud'],
		];
	}

	/**
	 * @dataProvider providesAuth
	 */
	public function testAuth(bool $expectedResult, string $user, string $password): void {
		if ($expectedResult) {
			$this->dbHandler->addServer('url1');
			$this->dbHandler->addSharedSecret('url1', $password);
		}
		$result = $this->dbHandler->auth($user, $password);
		$this->assertEquals($expectedResult, $result);
	}

	public static function providesAuth(): array {
		return [
			[false, 'foo', ''],
			[true, 'system', '123456789'],
		];
	}
}
