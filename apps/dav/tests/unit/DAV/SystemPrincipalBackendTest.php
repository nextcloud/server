<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\DAV\SystemPrincipalBackend;
use Sabre\DAV\Exception;
use Test\TestCase;

class SystemPrincipalBackendTest extends TestCase {

	/**
	 * @dataProvider providesPrefix
	 */
	public function testGetPrincipalsByPrefix(array $expected, string $prefix) {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getPrincipalsByPrefix($prefix);
		$this->assertEquals($expected, $result);
	}

	public function providesPrefix(): array {
		return [
			[[], ''],
			[[[
				'uri' => 'principals/system/system',
				'{DAV:}displayname' => 'system',
			],
				[
					'uri' => 'principals/system/public',
					'{DAV:}displayname' => 'public',
				]
			], 'principals/system'],
		];
	}

	/**
	 * @dataProvider providesPath
	 */
	public function testGetPrincipalByPath(?array $expected, string $path) {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getPrincipalByPath($path);
		$this->assertEquals($expected, $result);
	}

	public function providesPath(): array {
		return [
			[null, ''],
			[null, 'principals'],
			[null, 'principals/system'],
			[[
				'uri' => 'principals/system/system',
				'{DAV:}displayname' => 'system',
			], 'principals/system/system'],
		];
	}

	/**
	 * @dataProvider providesPrincipalForGetGroupMemberSet
	 * @throws Exception
	 */
	public function testGetGroupMemberSetExceptional(?string $principal) {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$backend = new SystemPrincipalBackend();
		$backend->getGroupMemberSet($principal);
	}

	public function providesPrincipalForGetGroupMemberSet(): array {
		return [
			[null],
			['principals/system'],
		];
	}

	/**
	 * @throws Exception
	 */
	public function testGetGroupMemberSet() {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getGroupMemberSet('principals/system/system');
		$this->assertEquals(['principals/system/system'], $result);
	}

	/**
	 * @dataProvider providesPrincipalForGetGroupMembership
	 * @throws Exception
	 */
	public function testGetGroupMembershipExceptional(string $principal) {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$backend = new SystemPrincipalBackend();
		$backend->getGroupMembership($principal);
	}

	public function providesPrincipalForGetGroupMembership(): array {
		return [
			['principals/system/a'],
		];
	}

	/**
	 * @throws Exception
	 */
	public function testGetGroupMembership() {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getGroupMembership('principals/system/system');
		$this->assertEquals([], $result);
	}
}
