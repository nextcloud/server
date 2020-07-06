<?php
/**
 *
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author MasterOfDeath <rinat.gumirov@mail.ru>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\ShareByMail\Tests;

use OCA\ShareByMail\Capabilities;
use OCA\ShareByMail\Settings\SettingsManager;
use Test\TestCase;

class CapabilitiesTest extends TestCase {
	/** @var Capabilities */
	private $capabilities;

	/** @var SettingsManager */
	private $settingsManager;

	protected function setUp(): void {
		parent::setUp();


		$this->settingsManager = $this::createMock(SettingsManager::class);
		$this->capabilities = new Capabilities($this->settingsManager);
	}

	public function testGetCapabilities() {
		$this->settingsManager->method('enforcePasswordProtection')
			->willReturn(false);

		$capabilities = [
			'files_sharing' =>
				[
					'sharebymail' =>
						[
							'enabled' => true,
							'upload_files_drop' => ['enabled' => true],
							'password' => ['enabled' => true, 'enforced' => false],
							'expire_date' => ['enabled' => true]
						]
				]
		];

		$this->assertSame($capabilities, $this->capabilities->getCapabilities());
	}
}
