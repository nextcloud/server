<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair\NC33;

use OC\Repair\NC33\FixDefaultAccountScopesToLocal;
use OCP\Accounts\IAccountManager;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class FixDefaultAccountScopesToLocalTest extends TestCase {
	private IDBConnection $connection;
	private IOutput&MockObject $output;
	private FixDefaultAccountScopesToLocal $repair;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->output = $this->createMock(IOutput::class);
		$this->repair = new FixDefaultAccountScopesToLocal($this->connection);
	}

	protected function tearDown(): void {
		parent::tearDown();
		$query = $this->connection->getQueryBuilder();
		$query->delete('accounts')
			->where($query->expr()->like('uid', $query->createNamedParameter('test-fix-scope-%')))
			->executeStatement();
	}

	private function insertAccount(string $uid, array $data): void {
		$query = $this->connection->getQueryBuilder();
		$query->insert('accounts')
			->values([
				'uid' => $query->createNamedParameter($uid),
				'data' => $query->createNamedParameter(json_encode($data)),
			])
			->executeStatement();
	}

	private function getAccountData(string $uid): ?array {
		$query = $this->connection->getQueryBuilder();
		$query->select('data')
			->from('accounts')
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)));
		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}
		return json_decode($row['data'], true);
	}

	public function testMigratesFederatedToLocal(): void {
		$uid = 'test-fix-scope-federated';
		$data = [
			IAccountManager::PROPERTY_DISPLAYNAME => [
				'value' => 'Test User',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => '0',
			],
			IAccountManager::PROPERTY_EMAIL => [
				'value' => 'test@example.com',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => '0',
			],
			IAccountManager::PROPERTY_AVATAR => [
				'value' => '',
				'scope' => IAccountManager::SCOPE_FEDERATED,
			],
			IAccountManager::PROPERTY_PRONOUNS => [
				'value' => '',
				'scope' => IAccountManager::SCOPE_FEDERATED,
			],
			IAccountManager::PROPERTY_PHONE => [
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => '0',
			],
			IAccountManager::PROPERTY_ADDRESS => [
				'value' => '',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => '0',
			],
		];

		$this->insertAccount($uid, $data);
		$this->repair->run($this->output);

		$updatedData = $this->getAccountData($uid);
		$this->assertNotNull($updatedData);

		// These should be changed from federated to local
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_DISPLAYNAME]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_EMAIL]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_AVATAR]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_PRONOUNS]['scope']);

		// These should remain unchanged
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_PHONE]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_ADDRESS]['scope']);
	}

	public function testDoesNotChangePublishedScope(): void {
		$uid = 'test-fix-scope-published';
		$data = [
			IAccountManager::PROPERTY_DISPLAYNAME => [
				'value' => 'Public User',
				'scope' => IAccountManager::SCOPE_PUBLISHED,
				'verified' => '0',
			],
			IAccountManager::PROPERTY_EMAIL => [
				'value' => 'public@example.com',
				'scope' => IAccountManager::SCOPE_PUBLISHED,
				'verified' => '0',
			],
		];

		$this->insertAccount($uid, $data);
		$this->repair->run($this->output);

		$updatedData = $this->getAccountData($uid);
		$this->assertNotNull($updatedData);

		// Published scope should NOT be changed
		$this->assertEquals(IAccountManager::SCOPE_PUBLISHED, $updatedData[IAccountManager::PROPERTY_DISPLAYNAME]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_PUBLISHED, $updatedData[IAccountManager::PROPERTY_EMAIL]['scope']);
	}

	public function testDoesNotChangeAlreadyLocalScope(): void {
		$uid = 'test-fix-scope-local';
		$data = [
			IAccountManager::PROPERTY_DISPLAYNAME => [
				'value' => 'Local User',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => '0',
			],
			IAccountManager::PROPERTY_EMAIL => [
				'value' => 'local@example.com',
				'scope' => IAccountManager::SCOPE_LOCAL,
				'verified' => '0',
			],
		];

		$this->insertAccount($uid, $data);
		$this->repair->run($this->output);

		$updatedData = $this->getAccountData($uid);
		$this->assertNotNull($updatedData);

		// Should remain local
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_DISPLAYNAME]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_LOCAL, $updatedData[IAccountManager::PROPERTY_EMAIL]['scope']);
	}

	public function testDoesNotChangeNonAffectedProperties(): void {
		$uid = 'test-fix-scope-phone-federated';
		$data = [
			IAccountManager::PROPERTY_PHONE => [
				'value' => '+1234567890',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => '0',
			],
			IAccountManager::PROPERTY_WEBSITE => [
				'value' => 'https://example.com',
				'scope' => IAccountManager::SCOPE_FEDERATED,
				'verified' => '0',
			],
		];

		$this->insertAccount($uid, $data);
		$this->repair->run($this->output);

		$updatedData = $this->getAccountData($uid);
		$this->assertNotNull($updatedData);

		// Phone and website were not in the old defaults that were federated,
		// so they should remain unchanged (user chose federated deliberately)
		$this->assertEquals(IAccountManager::SCOPE_FEDERATED, $updatedData[IAccountManager::PROPERTY_PHONE]['scope']);
		$this->assertEquals(IAccountManager::SCOPE_FEDERATED, $updatedData[IAccountManager::PROPERTY_WEBSITE]['scope']);
	}
}
