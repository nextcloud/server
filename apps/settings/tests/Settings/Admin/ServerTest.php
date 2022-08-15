<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\Settings\Tests\Settings\Admin;

use OC\Profile\ProfileManager;
use OCA\Settings\Settings\Admin\Server;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUrlGenerator;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 */
class ServerTest extends TestCase {
	/** @var Server */
	private $admin;
	/** @var IDBConnection */
	private $connection;
	/** @var IInitialState */
	private $initialStateService;
	/** @var ProfileManager */
	private $profileManager;
	/** @var ITimeFactory|MockObject */
	private $timeFactory;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IL10N|MockObject */
	private $l10n;
	/** @var IUrlGenerator|MockObject */
	private $urlGenerator;

	protected function setUp(): void {
		parent::setUp();
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->initialStateService = $this->createMock(IInitialState::class);
		$this->profileManager = $this->createMock(ProfileManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->urlGenerator = $this->createMock(IUrlGenerator::class);

		$this->admin = $this->getMockBuilder(Server::class)
			->onlyMethods(['cronMaxAge'])
			->setConstructorArgs([
				$this->connection,
				$this->initialStateService,
				$this->profileManager,
				$this->timeFactory,
				$this->urlGenerator,
				$this->config,
				$this->l10n,
			])
			->getMock();
	}

	public function testGetForm(): void {
		$this->admin->expects($this->once())
			->method('cronMaxAge')
			->willReturn(1337);
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnMap([
				['core', 'backgroundjobs_mode', 'ajax', 'ajax'],
				['core', 'lastcron', '0', '0'],
				['core', 'cronErrors', ''],
			]);
		$this->profileManager
			->expects($this->exactly(2))
			->method('isProfileEnabled')
			->willReturn(true);
		$expected = new TemplateResponse(
			'settings',
			'settings/admin/server',
			[
				'profileEnabledGlobally' => true,
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
