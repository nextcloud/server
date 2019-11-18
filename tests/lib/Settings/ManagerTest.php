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

namespace OCA\Settings\Tests\AppInfo;

use OCA\Settings\Admin\Sharing;
use OC\Settings\Manager;
use OCA\Settings\Personal\Security;
use OC\Settings\Section;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IServerContainer;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Settings\ISubAdminSettings;
use Test\TestCase;

class ManagerTest extends TestCase {

	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	private $manager;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $l10nFactory;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $url;
	/** @var IServerContainer|\PHPUnit_Framework_MockObject_MockObject */
	private $container;

	public function setUp() {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->container = $this->createMock(IServerContainer::class);

		$this->manager = new Manager(
			$this->logger,
			$this->l10nFactory,
			$this->url,
			$this->container
		);
	}

	public function testGetAdminSections() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));

		$this->manager->registerSection('admin', \OCA\WorkflowEngine\Settings\Section::class);

		$this->url->expects($this->exactly(6))
			->method('imagePath')
			->willReturnMap([
				['settings', 'admin.svg', '0'],
				['core', 'actions/settings-dark.svg', '1'],
				['core', 'actions/share.svg', '2'],
				['core', 'actions/password.svg', '3'],
				['core', 'places/contacts.svg', '5'],
				['settings', 'help.svg', '4'],
			]);

		$this->assertEquals([
			0 => [new Section('overview', 'Overview', 0, '0')],
			1 => [new Section('server', 'Basic settings', 0, '1')],
			5 => [new Section('sharing', 'Sharing', 0, '2')],
			10 => [new Section('security', 'Security', 0, '3')],
			50 => [new Section('groupware', 'Groupware', 0, '5')],
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
			98 => [new Section('additional', 'Additional settings', 0, '1')],
		], $this->manager->getAdminSections());
	}

	public function testGetPersonalSections() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));

		$this->manager->registerSection('personal', \OCA\WorkflowEngine\Settings\Section::class);

		$this->url->expects($this->exactly(3))
			->method('imagePath')
			->willReturnMap([
				['core', 'actions/info.svg', '1'],
				['settings', 'password.svg', '2'],
				['core', 'clients/phone.svg', '3'],
			]);

		$this->assertEquals([
			0 => [new Section('personal-info', 'Personal info', 0, '1')],
			5 => [new Section('security', 'Security', 0, '2')],
			15 => [new Section('sync-clients', 'Mobile & desktop', 0, '3')],
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
		], $this->manager->getPersonalSections());
	}

	public function testGetAdminSectionsEmptySection() {
		$this->l10nFactory
			->expects($this->once())
			->method('get')
			->with('lib')
			->willReturn($this->l10n);
		$this->l10n
			->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));

		$this->url->expects($this->exactly(6))
			->method('imagePath')
			->willReturnMap([
				['settings', 'admin.svg', '0'],
				['core', 'actions/settings-dark.svg', '1'],
				['core', 'actions/share.svg', '2'],
				['core', 'actions/password.svg', '3'],
				['core', 'places/contacts.svg', '5'],
				['settings', 'help.svg', '4'],
			]);

		$this->assertEquals([
			0 => [new Section('overview', 'Overview', 0, '0')],
			1 => [new Section('server', 'Basic settings', 0, '1')],
			5 => [new Section('sharing', 'Sharing', 0, '2')],
			10 => [new Section('security', 'Security', 0, '3')],
			50 => [new Section('groupware', 'Groupware', 0, '5')],
			98 => [new Section('additional', 'Additional settings', 0, '1')],
		], $this->manager->getAdminSections());
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
			->will($this->returnArgument(0));

		$this->url->expects($this->exactly(3))
			->method('imagePath')
			->willReturnMap([
				['core', 'actions/info.svg', '1'],
				['settings', 'password.svg', '2'],
				['core', 'clients/phone.svg', '3'],
			]);

		$this->assertArraySubset([
			0 => [new Section('personal-info', 'Personal info', 0, '1')],
			5 => [new Section('security', 'Security', 0, '2')],
			15 => [new Section('sync-clients', 'Mobile & desktop', 0, '3')],
		], $this->manager->getPersonalSections());
	}

	public function testGetAdminSettings() {
		$section = $this->createMock(Sharing::class);
		$section->expects($this->once())
			->method('getPriority')
			->willReturn(13);
		$this->container->expects($this->once())
			->method('query')
			->with(Sharing::class)
			->willReturn($section);

		$settings = $this->manager->getAdminSettings('sharing');

		$this->assertEquals([
			13 => [$section]
		], $settings);
	}

	public function testGetAdminSettingsAsSubAdmin() {
		$section = $this->createMock(Sharing::class);
		$this->container->expects($this->once())
			->method('query')
			->with(Sharing::class)
			->willReturn($section);

		$settings = $this->manager->getAdminSettings('sharing', true);

		$this->assertEquals([], $settings);
	}

	public function testGetSubAdminSettingsAsSubAdmin() {
		$section = $this->createMock(ISubAdminSettings::class);
		$section->expects($this->once())
			->method('getPriority')
			->willReturn(13);
		$this->container->expects($this->once())
			->method('query')
			->with(Sharing::class)
			->willReturn($section);

		$settings = $this->manager->getAdminSettings('sharing', true);

		$this->assertEquals([
			13 => [$section]
		], $settings);
	}

	public function testGetPersonalSettings() {
		$section = $this->createMock(Security::class);
		$section->expects($this->once())
			->method('getPriority')
			->willReturn(16);
		$section2 = $this->createMock(Security\Authtokens::class);
		$section2->expects($this->once())
			->method('getPriority')
			->willReturn(100);
		$this->container->expects($this->at(0))
			->method('query')
			->with(Security::class)
			->willReturn($section);
		$this->container->expects($this->at(1))
			->method('query')
			->with(Security\Authtokens::class)
			->willReturn($section2);

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
			->will($this->returnArgument(0));

		$this->manager->registerSection('personal', \OCA\WorkflowEngine\Settings\Section::class);
		$this->manager->registerSection('admin', \OCA\WorkflowEngine\Settings\Section::class);

		$this->url->expects($this->exactly(9))
			->method('imagePath')
			->willReturnMap([
				['core', 'actions/info.svg', '1'],
				['settings', 'password.svg', '2'],
				['core', 'clients/phone.svg', '3'],
				['settings', 'admin.svg', '0'],
				['core', 'actions/settings-dark.svg', '1'],
				['core', 'actions/share.svg', '2'],
				['core', 'actions/password.svg', '3'],
				['core', 'places/contacts.svg', '5'],
				['settings', 'help.svg', '4'],
			]);

		$this->assertEquals([
			0 => [new Section('personal-info', 'Personal info', 0, '1')],
			5 => [new Section('security', 'Security', 0, '2')],
			15 => [new Section('sync-clients', 'Mobile & desktop', 0, '3')],
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
		], $this->manager->getPersonalSections());

		$this->assertEquals([
			0 => [new Section('overview', 'Overview', 0, '0')],
			1 => [new Section('server', 'Basic settings', 0, '1')],
			5 => [new Section('sharing', 'Sharing', 0, '2')],
			10 => [new Section('security', 'Security', 0, '3')],
			50 => [new Section('groupware', 'Groupware', 0, '5')],
			55 => [\OC::$server->query(\OCA\WorkflowEngine\Settings\Section::class)],
			98 => [new Section('additional', 'Additional settings', 0, '1')],
		], $this->manager->getAdminSections());
	}
}
