<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Settings\Tests\AppInfo;

use OC\Settings\Manager;
use OCA\WorkflowEngine\Settings\Section;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Settings\ISettings;
use OCP\Settings\ISubAdminSettings;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	private IL10N&MockObject $l10n;

	private Manager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);

		$this->manager = $this->createInstanceWithMocks(Manager::class);
	}

	public function testGetAdminSections(): void {
		$this->manager->registerSection('admin', Section::class);

		$section = Server::get(Section::class);
		$this->mocks[ContainerInterface::class]->method('get')
			->with(Section::class)
			->willReturn($section);

		$this->assertEquals([
			55 => [$section],
		], $this->manager->getAdminSections());
	}

	public function testGetPersonalSections(): void {
		$this->manager->registerSection('personal', Section::class);

		$section = Server::get(Section::class);
		$this->mocks[ContainerInterface::class]->method('get')
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
		$this->mocks[IFactory::class]
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
		$this->mocks[ContainerInterface::class]->method('get')
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
		$this->mocks[ContainerInterface::class]->method('get')
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
		$this->mocks[ContainerInterface::class]->expects($this->once())
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

		$this->mocks[ContainerInterface::class]->expects($this->exactly(2))
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

	public function testGetPersonalSettingsHidesSettingsOfAppsNotEnabledForUser(): void {
		$visible = $this->createMock(ISettings::class);
		$visible->method('getPriority')
			->willReturn(16);
		$visible->method('getSection')
			->willReturn('security');

		$this->manager->registerSetting('personal', 'visibleClass', 'enabled_app');
		$this->manager->registerSetting('personal', 'hiddenClass', 'restricted_app');

		$this->mocks[IAppManager::class]->method('isEnabledForUser')
			->willReturnCallback(static fn (string $appId): bool => $appId === 'enabled_app');

		// The settings of the app the user has no access to are never instantiated.
		$this->mocks[ContainerInterface::class]->expects($this->once())
			->method('get')
			->with('visibleClass')
			->willReturn($visible);

		$this->assertEquals([
			16 => [$visible],
		], $this->manager->getPersonalSettings('security'));
	}

	public function testGetPersonalSectionsHidesSectionsOfAppsNotEnabledForUser(): void {
		$this->mocks[IFactory::class]->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n->method('t')
			->willReturnArgument(0);

		$this->manager->registerSection('personal', Section::class, 'restricted_app');

		$this->mocks[IAppManager::class]->method('isEnabledForUser')
			->with('restricted_app')
			->willReturn(false);

		$this->mocks[ContainerInterface::class]->expects($this->never())
			->method('get');

		$this->assertEquals([], $this->manager->getPersonalSections());
	}

	public function testGetAdminSettingsAreNotHiddenForAppsNotEnabledForUser(): void {
		// Admins configure apps they are not a member of themselves.
		$setting = $this->createMock(ISettings::class);
		$setting->method('getPriority')
			->willReturn(13);
		$setting->method('getSection')
			->willReturn('sharing');

		$this->manager->registerSetting('admin', 'myAdminClass', 'restricted_app');

		$this->mocks[IAppManager::class]->expects($this->never())
			->method('isEnabledForUser');
		$this->mocks[ContainerInterface::class]->method('get')
			->with('myAdminClass')
			->willReturn($setting);

		$this->assertEquals([
			13 => [$setting],
		], $this->manager->getAdminSettings('sharing'));
	}

	public function testSameSectionAsPersonalAndAdmin(): void {
		$this->mocks[IFactory::class]
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
		$this->mocks[ContainerInterface::class]->method('get')
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
