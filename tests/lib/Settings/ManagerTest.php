<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Settings\Tests\AppInfo;

use OC\Settings\AuthorizedGroupMapper;
use OC\Settings\Manager;
use OCA\WorkflowEngine\Settings\Section;
use OCP\Group\ISubAdmin;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Server;
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

	public function testGetAdminSections(): void {
		$this->manager->registerSection('admin', Section::class);

		$section = Server::get(Section::class);
		$this->container->method('get')
			->with(Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getAdminSections());
	}

	public function testGetPersonalSections(): void {
		$this->manager->registerSection('personal', Section::class);

		$section = Server::get(Section::class);
		$this->container->method('get')
			->with(Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getPersonalSections());
	}

	public function testGetAdminSectionsEmptySection(): void {
		$this->assertEquals([], $this->manager->getAdminSections());
	}

	public function testGetPersonalSectionsEmptySection(): void {
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

	public function testGetAdminSettings(): void {
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

	public function testGetAdminSettingsAsSubAdmin(): void {
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

	public function testGetSubAdminSettingsAsSubAdmin(): void {
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

	public function testGetPersonalSettings(): void {
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

	public function testSameSectionAsPersonalAndAdmin(): void {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->manager->registerSection('personal', Section::class);
		$this->manager->registerSection('admin', Section::class);


		$section = Server::get(Section::class);
		$this->container->method('get')
			->with(Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getPersonalSections());

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getAdminSections());
	}
}
