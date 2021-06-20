<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\UserStatus\Tests\Service;

use OCA\UserStatus\Service\EmojiService;
use OCP\IDBConnection;
use Test\TestCase;

class EmojiServiceTest extends TestCase {

	/** @var IDBConnection|\PHPUnit\Framework\MockObject\MockObject */
	private $db;

	/** @var EmojiService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->db = $this->createMock(IDBConnection::class);
		$this->service = new EmojiService($this->db);
	}

	/**
	 * @param bool $supports4ByteText
	 * @param bool $expected
	 *
	 * @dataProvider doesPlatformSupportEmojiDataProvider
	 */
	public function testDoesPlatformSupportEmoji(bool $supports4ByteText, bool $expected): void {
		$this->db->expects($this->once())
			->method('supports4ByteText')
			->willReturn($supports4ByteText);

		$this->assertEquals($expected, $this->service->doesPlatformSupportEmoji());
	}

	/**
	 * @return array
	 */
	public function doesPlatformSupportEmojiDataProvider(): array {
		return [
			[true, true],
			[false, false],
		];
	}

	/**
	 * @param string $emoji
	 * @param bool $expected
	 *
	 * @dataProvider isValidEmojiDataProvider
	 */
	public function testIsValidEmoji(string $emoji, bool $expected): void {
		$actual = $this->service->isValidEmoji($emoji);

		$this->assertEquals($expected, $actual);
	}

	public function isValidEmojiDataProvider(): array {
		return [
			['🏝', true],
			['📱', true],
			['🏢', true],
			['📱📠', false],
			['a', false],
			['0', false],
			['$', false],
			// Test some more complex emojis with modifiers and zero-width-joiner
			['👩🏿‍💻', true],
			['🤷🏼‍♀️', true],
			['🏳️‍🌈', true],
			['👨‍👨‍👦‍👦', true],
			['👩‍❤️‍👩', true]
		];
	}
}
