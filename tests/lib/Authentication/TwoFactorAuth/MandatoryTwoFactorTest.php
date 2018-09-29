<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace Tests\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class MandatoryTwoFactorTest extends TestCase {

	/** @var IConfig|MockObject */
	private $config;

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);

		$this->mandatoryTwoFactor = new MandatoryTwoFactor($this->config);
	}

	public function testIsNotEnforced() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('twofactor_enforced', 'false')
			->willReturn('false');

		$isEnforced = $this->mandatoryTwoFactor->isEnforced();

		$this->assertFalse($isEnforced);
	}

	public function testIsEnforced() {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('twofactor_enforced', 'false')
			->willReturn('true');

		$isEnforced = $this->mandatoryTwoFactor->isEnforced();

		$this->assertTrue($isEnforced);
	}

	public function testSetEnforced() {
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('twofactor_enforced', 'true');

		$this->mandatoryTwoFactor->setEnforced(true);
	}

	public function testSetNotEnforced() {
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('twofactor_enforced', 'false');

		$this->mandatoryTwoFactor->setEnforced(false);
	}

}
