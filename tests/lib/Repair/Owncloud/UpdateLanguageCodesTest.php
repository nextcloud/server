<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair\Owncloud;

use OC\Repair\Owncloud\UpdateLanguageCodes;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class UpdateLanguageCodesTest
 *
 * @group DB
 *
 * @package Test\Repair
 */
class UpdateLanguageCodesTest extends TestCase {

	protected IDBConnection $connection;
	private IConfig&MockObject $config;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = Server::get(IDBConnection::class);
		$this->config = $this->createMock(IConfig::class);
	}

	public function testRun(): void {
		$users = [
			['userid' => 'user1', 'configvalue' => 'fi_FI'],
			['userid' => 'user2', 'configvalue' => 'de'],
			['userid' => 'user3', 'configvalue' => 'fi'],
			['userid' => 'user4', 'configvalue' => 'ja'],
			['userid' => 'user5', 'configvalue' => 'bg_BG'],
			['userid' => 'user6', 'configvalue' => 'ja'],
			['userid' => 'user7', 'configvalue' => 'th_TH'],
			['userid' => 'user8', 'configvalue' => 'th_TH'],
		];

		// insert test data
		$qb = $this->connection->getQueryBuilder();
		$qb->insert('preferences')
			->values([
				'userid' => $qb->createParameter('userid'),
				'appid' => $qb->createParameter('appid'),
				'configkey' => $qb->createParameter('configkey'),
				'configvalue' => $qb->createParameter('configvalue'),
			]);
		foreach ($users as $user) {
			$qb->setParameters([
				'userid' => $user['userid'],
				'appid' => 'core',
				'configkey' => 'lang',
				'configvalue' => $user['configvalue'],
			])->executeStatement();
		}

		// check if test data is written to DB
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['userid', 'configvalue'])
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter('core')))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('lang')))
			->orderBy('userid')
			->executeQuery();

		$rows = $result->fetchAll();
		$result->closeCursor();

		$this->assertSame($users, $rows, 'Asserts that the entries are the ones from the test data set');

		$expectedOutput = [
			['Changed 1 setting(s) from "bg_BG" to "bg" in preferences table.'],
			['Changed 0 setting(s) from "cs_CZ" to "cs" in preferences table.'],
			['Changed 1 setting(s) from "fi_FI" to "fi" in preferences table.'],
			['Changed 0 setting(s) from "hu_HU" to "hu" in preferences table.'],
			['Changed 0 setting(s) from "nb_NO" to "nb" in preferences table.'],
			['Changed 0 setting(s) from "sk_SK" to "sk" in preferences table.'],
			['Changed 2 setting(s) from "th_TH" to "th" in preferences table.'],
		];
		$outputMessages = [];
		/** @var IOutput&MockObject $outputMock */
		$outputMock = $this->createMock(IOutput::class);
		$outputMock->expects($this->exactly(7))
			->method('info')
			->willReturnCallback(function () use (&$outputMessages): void {
				$outputMessages[] = func_get_args();
			});

		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('version', '0.0.0')
			->willReturn('12.0.0.13');

		// run repair step
		$repair = new UpdateLanguageCodes($this->connection, $this->config);
		$repair->run($outputMock);

		// check if test data is correctly modified in DB
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select(['userid', 'configvalue'])
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter('core')))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('lang')))
			->orderBy('userid')
			->executeQuery();

		$rows = $result->fetchAll();
		$result->closeCursor();

		// value has changed for one user
		$users[0]['configvalue'] = 'fi';
		$users[4]['configvalue'] = 'bg';
		$users[6]['configvalue'] = 'th';
		$users[7]['configvalue'] = 'th';
		$this->assertSame($users, $rows, 'Asserts that the entries are updated correctly.');

		// remove test data
		foreach ($users as $user) {
			$qb = $this->connection->getQueryBuilder();
			$qb->delete('preferences')
				->where($qb->expr()->eq('userid', $qb->createNamedParameter($user['userid'])))
				->andWhere($qb->expr()->eq('appid', $qb->createNamedParameter('core')))
				->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('lang')))
				->andWhere($qb->expr()->eq('configvalue', $qb->createNamedParameter($user['configvalue']), IQueryBuilder::PARAM_STR))
				->executeStatement();
		}
		self::assertEquals($expectedOutput, $outputMessages);
	}

	public function testSecondRun(): void {
		/** @var IOutput&MockObject $outputMock */
		$outputMock = $this->createMock(IOutput::class);
		$outputMock->expects($this->never())
			->method('info');

		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('version', '0.0.0')
			->willReturn('12.0.0.14');

		// run repair step
		$repair = new UpdateLanguageCodes($this->connection, $this->config);
		$repair->run($outputMock);
	}
}
