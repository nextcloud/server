<?php
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
 */

/**
 * Created by PhpStorm.
 * User: christoph
 * Date: 11.10.18
 * Time: 13:01
 */

namespace Tests\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use Test\TestCase;

class EnforcementStateTest extends TestCase {
	public function testIsEnforced() {
		$state = new EnforcementState(true);

		$this->assertTrue($state->isEnforced());
	}

	public function testGetEnforcedGroups() {
		$state = new EnforcementState(true, ['twofactorers']);

		$this->assertEquals(['twofactorers'], $state->getEnforcedGroups());
	}

	public function testGetExcludedGroups() {
		$state = new EnforcementState(true, [], ['yoloers']);

		$this->assertEquals(['yoloers'], $state->getExcludedGroups());
	}

	public function testJsonSerialize() {
		$state = new EnforcementState(true, ['twofactorers'], ['yoloers']);
		$expected = [
			'enforced' => true,
			'enforcedGroups' => ['twofactorers'],
			'excludedGroups' => ['yoloers'],
		];

		$json = $state->jsonSerialize();

		$this->assertEquals($expected, $json);
	}
}
