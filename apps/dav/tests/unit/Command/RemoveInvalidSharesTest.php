<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2018 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\Unit\Command;

use OCA\DAV\Command\RemoveInvalidShares;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\Migration\IOutput;
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
		$db = \OC::$server->getDatabaseConnection();

		$db->insertIfNotExist('*PREFIX*dav_shares', [
			'principaluri' => 'principal:unknown',
			'type' => 'calendar',
			'access' => 2,
			'resourceid' => 666,
		]);
	}

	public function test(): void {
		$db = \OC::$server->getDatabaseConnection();
		/** @var Principal | \PHPUnit\Framework\MockObject\MockObject $principal */
		$principal = $this->createMock(Principal::class);

		/** @var IOutput | \PHPUnit\Framework\MockObject\MockObject $output */
		$output = $this->createMock(IOutput::class);

		$repair = new RemoveInvalidShares($db, $principal);
		$this->invokePrivate($repair, 'run', [$this->createMock(InputInterface::class), $this->createMock(OutputInterface::class)]);

		$query = $db->getQueryBuilder();
		$result = $query->select('*')->from('dav_shares')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter('principal:unknown')))->execute();
		$data = $result->fetchAll();
		$result->closeCursor();
		$this->assertEquals(0, count($data));
	}
}
