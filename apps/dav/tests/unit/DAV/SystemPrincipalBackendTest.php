<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\DAV;

use OCA\DAV\DAV\SystemPrincipalBackend;
use Sabre\DAV\Exception;
use Test\TestCase;

class SystemPrincipalBackendTest extends TestCase {

	/**
	 * @dataProvider providesPrefix
	 */
	public function testGetPrincipalsByPrefix(array $expected, string $prefix): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getPrincipalsByPrefix($prefix);
		$this->assertEquals($expected, $result);
	}

	public static function providesPrefix(): array {
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
	public function testGetPrincipalByPath(?array $expected, string $path): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getPrincipalByPath($path);
		$this->assertEquals($expected, $result);
	}

	public static function providesPath(): array {
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
	 */
	public function testGetGroupMemberSetExceptional(?string $principal): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$backend = new SystemPrincipalBackend();
		$backend->getGroupMemberSet($principal);
	}

	public static function providesPrincipalForGetGroupMemberSet(): array {
		return [
			[null],
			['principals/system'],
		];
	}

	public function testGetGroupMemberSet(): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getGroupMemberSet('principals/system/system');
		$this->assertEquals(['principals/system/system'], $result);
	}

	/**
	 * @dataProvider providesPrincipalForGetGroupMembership
	 */
	public function testGetGroupMembershipExceptional(string $principal): void {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Principal not found');

		$backend = new SystemPrincipalBackend();
		$backend->getGroupMembership($principal);
	}

	public static function providesPrincipalForGetGroupMembership(): array {
		return [
			['principals/system/a'],
		];
	}

	public function testGetGroupMembership(): void {
		$backend = new SystemPrincipalBackend();
		$result = $backend->getGroupMembership('principals/system/system');
		$this->assertEquals([], $result);
	}
}
