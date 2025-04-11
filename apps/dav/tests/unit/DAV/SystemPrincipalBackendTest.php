<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\DAV\SystemPrincipalBackend;
use Test\TestCase;

class SystemPrincipalBackendTest extends TestCase {

	/**
	 * @dataProvider providesPrefix
	 * @param $expected
	 * @param $prefix
	 */
	public function testGetPrincipalsByPrefix($expected, $prefix): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getPrincipalsByPrefix($prefix);
		$this->assertEquals($expected, $result);
	}

	public function providesPrefix() {
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
	 * @param $expected
	 * @param $path
	 */
	public function testGetPrincipalByPath($expected, $path): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getPrincipalByPath($path);
		$this->assertEquals($expected, $result);
	}

	public function providesPath() {
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
	 *
	 * @param string $principal
	 * @throws \Sabre\DAV\Exception
	 */
	public function testGetGroupMemberSetExceptional($principal): void {
		$this->expectException(\Sabre\DAV\Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$backend = new SystemPrincipalBackend();
		$backend->getGroupMemberSet($principal);
	}

	public function providesPrincipalForGetGroupMemberSet() {
		return [
			[null],
			['principals/system'],
		];
	}

	/**
	 * @throws \Sabre\DAV\Exception
	 */
	public function testGetGroupMemberSet(): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getGroupMemberSet('principals/system/system');
		$this->assertEquals(['principals/system/system'], $result);
	}

	/**
	 * @dataProvider providesPrincipalForGetGroupMembership
	 *
	 * @param string $principal
	 * @throws \Sabre\DAV\Exception
	 */
	public function testGetGroupMembershipExceptional($principal): void {
		$this->expectException(\Sabre\DAV\Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$backend = new SystemPrincipalBackend();
		$backend->getGroupMembership($principal);
	}

	public function providesPrincipalForGetGroupMembership() {
		return [
			['principals/system/a'],
		];
	}

	/**
	 * @throws \Sabre\DAV\Exception
	 */
	public function testGetGroupMembership(): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getGroupMembership('principals/system/system');
		$this->assertEquals([], $result);
	}
}
