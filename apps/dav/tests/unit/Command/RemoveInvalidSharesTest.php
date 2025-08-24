<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Command;

use OCA\DAV\Command\RemoveInvalidShares;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\IDBConnection;
use OCP\Server;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class RemoveInvalidSharesTest
 *
 * @package OCA\DAV\Tests\Unit\Repair
 * @group DB
 */
class RemoveInvalidSharesTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		$db = Server::get(IDBConnection::class);

		$db->insertIfNotExist('*PREFIX*dav_shares', [
			'principaluri' => 'principal:unknown',
			'type' => 'calendar',
			'access' => 2,
			'resourceid' => 666,
		]);
	}

	public function test(): void {
		$db = Server::get(IDBConnection::class);
		$principal = $this->createMock(Principal::class);

		$repair = new RemoveInvalidShares($db, $principal);
		$this->invokePrivate($repair, 'run', [$this->createMock(InputInterface::class), $this->createMock(OutputInterface::class)]);

		$query = $db->getQueryBuilder();
		$query->select('*')
			->from('dav_shares')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter('principal:unknown')));
		$result = $query->executeQuery();
		$data = $result->fetchAll();
		$result->closeCursor();
		$this->assertEquals(0, count($data));
	}
}
