<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tests\Settings;

use OC\Settings\Admin\Sharing;
use OC\Settings\Manager;
use OC\Settings\Section;
use OCP\Encryption\IManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var Manager */
	private $manager;
	/** @var ILogger */
	private $logger;
	/** @var IDBConnection */
	private $dbConnection;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var IManager */
	private $encryptionManager;
	/** @var IUserManager */
	private $userManager;
	/** @var ILockingProvider */
	private $lockingProvider;

	public function setUp() {
		parent::setUp();

		$this->logger = $this->createMock('\OCP\ILogger');
		$this->dbConnection = $this->createMock('\OCP\IDBConnection');
		$this->l10n = $this->createMock('\OCP\IL10N');
		$this->config = $this->createMock('\OCP\IConfig');
		$this->encryptionManager = $this->createMock('\OCP\Encryption\IManager');
		$this->userManager = $this->createMock('\OCP\IUserManager');
		$this->lockingProvider = $this->createMock('\OCP\Lock\ILockingProvider');

		$this->manager = new Manager(
			$this->logger,
			$this->dbConnection,
			$this->l10n,
			$this->config,
			$this->encryptionManager,
			$this->userManager,
			$this->lockingProvider
		);
	}

	public function testSetupSettings() {
		$qb = $this->createMock('\OCP\DB\QueryBuilder\IQueryBuilder');
		$qb
			->expects($this->once())
			->method('select')
			->with('class')
			->willReturn($qb);
		$this->dbConnection
			->expects($this->at(0))
			->method('getQueryBuilder')
			->willReturn($qb);
		$qb
			->expects($this->once())
			->method('from')
			->with('admin_settings')
			->willReturn($qb);
		$expressionBuilder = $this->createMock('\OCP\DB\QueryBuilder\IExpressionBuilder');
		$qb
			->expects($this->once())
			->method('expr')
			->willReturn($expressionBuilder);
		$param = $this->createMock('\OCP\DB\QueryBuilder\IParameter');
		$qb
			->expects($this->once())
			->method('createNamedParameter')
			->with('OCA\Files\Settings\Admin')
			->willReturn($param);
		$expressionBuilder
			->expects($this->once())
			->method('eq')
			->with('class', $param)
			->willReturn('myString');
		$qb
			->expects($this->once())
			->method('where')
			->with('myString')
			->willReturn($qb);
		$stmt = $this->createMock('\Doctrine\DBAL\Driver\Statement');
		$qb
			->expects($this->once())
			->method('execute')
			->willReturn($stmt);

		$qb1 = $this->createMock('\OCP\DB\QueryBuilder\IQueryBuilder');
		$qb1
			->expects($this->once())
			->method('insert')
			->with('admin_settings')
			->willReturn($qb1);
		$this->dbConnection
			->expects($this->at(1))
			->method('getQueryBuilder')
			->willReturn($qb1);

		$this->manager->setupSettings([
			'admin' => 'OCA\Files\Settings\Admin',
		]);
	}

	public function testGetAdminSections() {
		$qb = $this->createMock('\OCP\DB\QueryBuilder\IQueryBuilder');
		$qb
			->expects($this->once())
			->method('select')
			->with(['class', 'priority'])
			->willReturn($qb);
		$qb
			->expects($this->once())
			->method('from')
			->with('admin_sections')
			->willReturn($qb);
		$stmt = $this->createMock('\Doctrine\DBAL\Driver\Statement');
		$qb
			->expects($this->once())
			->method('execute')
			->willReturn($stmt);
		$this->dbConnection
			->expects($this->once())
			->method('getQueryBuilder')
			->willReturn($qb);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));

		$this->assertEquals([
			0 => [new Section('server', 'Server settings', 0)],
			5 => [new Section('sharing', 'Sharing', 0)],
			45 => [new Section('encryption', 'Encryption', 0)],
			90 => [new Section('logging', 'Logging', 0)],
			98 => [new Section('additional', 'Additional settings', 0)],
			99 => [new Section('tips-tricks', 'Tips & tricks', 0)],
		], $this->manager->getAdminSections());
	}

	public function testGetAdminSettings() {
		$qb = $this->createMock('\OCP\DB\QueryBuilder\IQueryBuilder');
		$qb
			->expects($this->once())
			->method('select')
			->with(['class', 'priority'])
			->willReturn($qb);
		$qb
			->expects($this->once())
			->method('from')
			->with('admin_settings')
			->willReturn($qb);
		$expressionBuilder = $this->createMock('\OCP\DB\QueryBuilder\IExpressionBuilder');
		$qb
			->expects($this->once())
			->method('expr')
			->willReturn($expressionBuilder);
		$param = $this->createMock('\OCP\DB\QueryBuilder\IParameter');
		$qb
			->expects($this->once())
			->method('createParameter')
			->with('section')
			->willReturn($param);
		$expressionBuilder
			->expects($this->once())
			->method('eq')
			->with('section', $param)
			->willReturn('myString');
		$qb
			->expects($this->once())
			->method('where')
			->with('myString')
			->willReturn($qb);
		$stmt = $this->createMock('\Doctrine\DBAL\Driver\Statement');
		$qb
			->expects($this->once())
			->method('execute')
			->willReturn($stmt);
		$this->dbConnection
			->expects($this->exactly(2))
			->method('getQueryBuilder')
			->willReturn($qb);

		$this->assertEquals([
			0 => [new Sharing($this->config)],
		], $this->manager->getAdminSettings('sharing'));
	}
}
