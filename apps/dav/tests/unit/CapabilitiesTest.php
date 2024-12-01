<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\unit;

use OCA\DAV\Capabilities;
use OCP\IConfig;
use OCP\User\IAvailabilityCoordinator;
use Test\TestCase;

/**
 * @package OCA\DAV\Tests\unit
 */
class CapabilitiesTest extends TestCase {
	public function testGetCapabilities(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn(false);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$capabilities = new Capabilities($config, $coordinator);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithBulkUpload(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn(true);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$capabilities = new Capabilities($config, $coordinator);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'bulkupload' => '1.0',
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}

	public function testGetCapabilitiesWithAbsence(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValueBool')
			->with('bulkupload.enabled', $this->isType('bool'))
			->willReturn(false);
		$coordinator = $this->createMock(IAvailabilityCoordinator::class);
		$coordinator->expects($this->once())
			->method('isEnabled')
			->willReturn(true);
		$capabilities = new Capabilities($config, $coordinator);
		$expected = [
			'dav' => [
				'chunking' => '1.0',
				'absence-supported' => true,
			],
		];
		$this->assertSame($expected, $capabilities->getCapabilities());
	}
}
