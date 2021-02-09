<?php
/**
 * @author Konrad Abicht <hi@inspirito.de>
 *
 * @copyright Copyright (c) 2021, Konrad Abicht <hi@inspirito.de>
 * @license AGPL-3.0
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

namespace Tests\Core\Data;

use JsonSerializable;
use OC\Core\Data\LoginFlowV2Credentials;
use Test\TestCase;

class LoginFlowV2CredentialsTest extends TestCase {

	private function createInstance(string $server, string $loginName, string $appPassword) {
		return new LoginFlowV2Credentials($server, $loginName, $appPassword);
	}

	public function testImplementsJsonSerializable() {
		$fixture = $this->createInstance('server', 'login', 'pass');

		$this->assertTrue($fixture instanceof JsonSerializable);
	}

	/**
	 * Test getter functions.
	 */
	public function testGetter() {
		$fixture = $this->createInstance('server', 'login', 'pass');

		$this->assertEquals('server', $fixture->getServer());
		$this->assertEquals('login', $fixture->getLoginName());
		$this->assertEquals('pass', $fixture->getAppPassword());
	}

	public function testJsonSerialize() {
		$fixture = $this->createInstance('server', 'login', 'pass');

		$this->assertEquals(
			[
				'server' => 'server',
				'loginName' => 'login',
				'appPassword' => 'pass',
			],
			$fixture->jsonSerialize()
		);
	}
}
