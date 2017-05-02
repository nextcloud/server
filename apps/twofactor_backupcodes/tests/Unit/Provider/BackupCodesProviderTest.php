<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\TwoFactorBackupCodes\Tests\Unit\Provider;

use OC\App\AppManager;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\IL10N;
use OCP\IUser;
use OCP\Template;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class BackupCodesProviderTest extends TestCase {

	/** @var string */
	private $appName;

	/** @var BackupCodeStorage|PHPUnit_Framework_MockObject_MockObject */
	private $storage;

	/** @var IL10N|PHPUnit_Framework_MockObject_MockObject */
	private $l10n;

	/** @var AppManager|PHPUnit_Framework_MockObject_MockObject */
	private $appManager;

	/** @var BackupCodesProvider */
	private $provider;

	protected function setUp() {
		parent::setUp();

		$this->appName = "twofactor_backupcodes";
		$this->storage = $this->createMock(BackupCodeStorage::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->appManager = $this->createMock(AppManager::class);

		$this->provider = new BackupCodesProvider($this->appName, $this->storage, $this->l10n, $this->appManager);
	}

	public function testGetId() {
		$this->assertEquals('backup_codes', $this->provider->getId());
	}

	public function testGetDisplayName() {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Backup code')
			->will($this->returnValue('l10n backup code'));
		$this->assertSame('l10n backup code', $this->provider->getDisplayName());
	}

	public function testGetDescription() {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Use backup code')
			->will($this->returnValue('l10n use backup code'));
		$this->assertSame('l10n use backup code', $this->provider->getDescription());
	}

	public function testGetTempalte() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$expected = new Template('twofactor_backupcodes', 'challenge');

		$this->assertEquals($expected, $this->provider->getTemplate($user));
	}

	public function testVerfiyChallenge() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$challenge = 'xyz';

		$this->storage->expects($this->once())
			->method('validateCode')
			->with($user, $challenge)
			->will($this->returnValue(false));

		$this->assertFalse($this->provider->verifyChallenge($user, $challenge));
	}

	public function testIsTwoFactorEnabledForUser() {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$this->storage->expects($this->once())
			->method('hasBackupCodes')
			->with($user)
			->will($this->returnValue(true));

		$this->assertTrue($this->provider->isTwoFactorAuthEnabledForUser($user));
	}

	public function testIsActiveNoProviders() {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn([
				'twofactor_backupcodes',
				'mail',
		]);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('mail')
			->willReturn([
				'two-factor-providers' => [],
		]);

		$this->assertFalse($this->provider->isActive($user));
	}

	public function testIsActiveWithProviders() {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn([
				'twofactor_backupcodes',
				'twofactor_u2f',
		]);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('twofactor_u2f')
			->willReturn([
				'two-factor-providers' => [
					'OCA\TwoFactorU2F\Provider\U2FProvider',
				],
		]);

		$this->assertTrue($this->provider->isActive($user));
	}

}
