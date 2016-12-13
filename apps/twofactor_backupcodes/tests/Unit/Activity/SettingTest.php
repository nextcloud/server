<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Copyright (c) 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Two-factor backup codes
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\TwoFactorBackupCodes\Test\Unit\Activity;

use OCA\TwoFactorBackupCodes\Activity\GenericSetting;
use OCP\IL10N;
use Test\TestCase;

class SettingTest extends TestCase {

	private $l10n;

	/** @var GenericSetting */
	private $setting;

	protected function setUp() {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);

		$this->setting = new GenericSetting($this->l10n);
	}

	public function testCanChangeMail() {
		$this->assertFalse($this->setting->canChangeMail());
	}

	public function testCanChangeStream() {
		$this->assertFalse($this->setting->canChangeStream());
	}

	public function testGetIdentifier() {
		$this->assertEquals('twofactor', $this->setting->getIdentifier());
	}

	public function testGetName() {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Two-factor authentication')
			->will($this->returnValue('Zwei-Faktor-Authentifizierung'));
		$this->assertEquals('Zwei-Faktor-Authentifizierung', $this->setting->getName());
	}

	public function testGetPriority() {
		$this->assertEquals(30, $this->setting->getPriority());
	}

	public function testIsDefaultEnabled() {
		$this->assertTrue($this->setting->isDefaultEnabledMail());
		$this->assertTrue($this->setting->isDefaultEnabledStream());
	}

}
