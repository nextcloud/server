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

namespace Tests\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Core\Command\TwoFactorAuth\Enforce;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class EnforceTest extends TestCase {

	/** @var MandatoryTwoFactor|MockObject */
	private $mandatoryTwoFactor;

	/** @var CommandTester */
	private $command;

	protected function setUp() {
		parent::setUp();

		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);
		$command = new Enforce($this->mandatoryTwoFactor);

		$this->command = new CommandTester($command);
	}

	public function testEnforce() {
		$this->mandatoryTwoFactor->expects($this->once())
			->method('setEnforced')
			->with(true);
		$this->mandatoryTwoFactor->expects($this->once())
			->method('isEnforced')
			->willReturn(true);

		$rc = $this->command->execute([
			'--on' => true,
		]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertContains("Two-factor authentication is enforced for all users", $display);
	}

	public function testDisableEnforced() {
		$this->mandatoryTwoFactor->expects($this->once())
			->method('setEnforced')
			->with(false);
		$this->mandatoryTwoFactor->expects($this->once())
			->method('isEnforced')
			->willReturn(false);

		$rc = $this->command->execute([
			'--off' => true,
		]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertContains("Two-factor authentication is not enforced", $display);
	}

	public function testCurrentStateEnabled() {
		$this->mandatoryTwoFactor->expects($this->once())
			->method('isEnforced')
			->willReturn(true);

		$rc = $this->command->execute([]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertContains("Two-factor authentication is enforced for all users", $display);
	}

	public function testCurrentStateDisabled() {
		$this->mandatoryTwoFactor->expects($this->once())
			->method('isEnforced')
			->willReturn(false);

		$rc = $this->command->execute([]);

		$this->assertEquals(0, $rc);
		$display = $this->command->getDisplay();
		$this->assertContains("Two-factor authentication is not enforced", $display);
	}

}
