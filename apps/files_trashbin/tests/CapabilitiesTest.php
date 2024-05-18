<?php
/**
 * @copyright Copyright (c) 2016 Unknown <anpz.asutp@gmail.com>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Unknown <anpz.asutp@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files_Trashbin\Tests;

use OCA\Files_Trashbin\Capabilities;
use Test\TestCase;

class CapabilitiesTest extends TestCase {

	/** @var Capabilities */
	private $capabilities;

	protected function setUp(): void {
		parent::setUp();
		$this->capabilities = new Capabilities();
	}
	
	public function testGetCapabilities() {
		$capabilities = [
			'files' => [
				'undelete' => true
			]
		];

		$this->assertSame($capabilities, $this->capabilities->getCapabilities());
	}
}
