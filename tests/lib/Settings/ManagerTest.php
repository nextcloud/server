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

namespace OC\Settings\Tests\AppInfo;

use OC\Settings\AuthorizedGroupMapper;
use OC\Settings\Manager;
use OCP\Group\ISubAdmin;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Settings\ISettings;
use OCP\Settings\ISubAdminSettings;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var Manager|MockObject */
	private $manager;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IDBConnection|MockObject */
	private $l10n;
	/** @var IFactory|MockObject */
	private $l10nFactory;
	/** @var IURLGenerator|MockObject */
	private $url;
	/** @var IServerContainer|MockObject */
	private $container;
	/** @var AuthorizedGroupMapper|MockObject */
	private $mapper;
	/** @var IGroupManager|MockObject */
	private $groupManager;
	/** @var ISubAdmin|MockObject */
	private $subAdmin;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->container = $this->createMock(IServerContainer::class);
		$this->mapper = $this->createMock(AuthorizedGroupMapper::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->subAdmin = $this->createMock(ISubAdmin::class);

		$this->manager = new Manager(
			$this->logger,
			$this->l10nFactory,
			$this->url,
			$this->container,
			$this->mapper,
			$this->groupManager,
			$this->subAdmin,
		);
	}

	public function testGetAdminSections() {
		$this->manager->registerSection('admin', \OCA\WorkflowEngine\Settings\Section::class);

		$section = \OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class);
		$this->container->method('get')
			->with(\OCA\WorkflowEngine\Settings\Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getAdminSections());
	}

	public function testGetPersonalSections() {
		$this->manager->registerSection('personal', \OCA\WorkflowEngine\Settings\Section::class);

		$section = \OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class);
		$this->container->method('get')
			->with(\OCA\WorkflowEngine\Settings\Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getPersonalSections());
	}

	public function testGetAdminSectionsEmptySection() {
		$this->assertEquals([], $this->manager->getAdminSections());
	}

	public function testGetPersonalSectionsEmptySection() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals([], $this->manager->getPersonalSections());
	}

	public function testGetAdminSettings() {
		$section = $this->createMock(ISettings::class);
		$section->method('getPriority')
			->willReturn(13);
		$section->method('getSection')
			->willReturn('sharing');
		$this->container->method('get')
			->with('myAdminClass')
			->willReturn($section);

		$this->manager->registerSetting('admin', 'myAdminClass');
		$settings = $this->manager->getAdminSettings('sharing');

		$this->assertEquals([
			13 => [$section]
		], $settings);
	}

	public function testGetAdminSettingsAsSubAdmin() {
		$section = $this->createMock(ISettings::class);
		$section->method('getPriority')
			->willReturn(13);
		$section->method('getSection')
			->willReturn('sharing');
		$this->container->method('get')
			->with('myAdminClass')
			->willReturn($section);

		$this->manager->registerSetting('admin', 'myAdminClass');
		$settings = $this->manager->getAdminSettings('sharing', true);

		$this->assertEquals([], $settings);
	}

	public function testGetSubAdminSettingsAsSubAdmin() {
		$section = $this->createMock(ISubAdminSettings::class);
		$section->method('getPriority')
			->willReturn(13);
		$section->method('getSection')
			->willReturn('sharing');
		$this->container->expects($this->once())
			->method('get')
			->with('mySubAdminClass')
			->willReturn($section);

		$this->manager->registerSetting('admin', 'mySubAdminClass');
		$settings = $this->manager->getAdminSettings('sharing', true);

		$this->assertEquals([
			13 => [$section]
		], $settings);
	}

	public function testGetPersonalSettings() {
		$section = $this->createMock(ISettings::class);
		$section->method('getPriority')
			->willReturn(16);
		$section->method('getSection')
			->willReturn('security');
		$section2 = $this->createMock(ISettings::class);
		$section2->method('getPriority')
			->willReturn(100);
		$section2->method('getSection')
			->willReturn('security');

		$this->manager->registerSetting('personal', 'section1');
		$this->manager->registerSetting('personal', 'section2');

		$this->container->expects($this->exactly(2))
			->method('get')
			->withConsecutive(
				['section1'],
				['section2']
			)
			->willReturnMap([
				['section1', $section],
				['section2', $section2],
			]);

		$settings = $this->manager->getPersonalSettings('security');

		$this->assertEquals([
			16 => [$section],
			100 => [$section2],
		], $settings);
	}

	public function testSameSectionAsPersonalAndAdmin() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->manager->registerSection('personal', \OCA\WorkflowEngine\Settings\Section::class);
		$this->manager->registerSection('admin', \OCA\WorkflowEngine\Settings\Section::class);


		$section = \OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class);
		$this->container->method('get')
			->with(\OCA\WorkflowEngine\Settings\Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getPersonalSections());

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getAdminSections());
	}
}
