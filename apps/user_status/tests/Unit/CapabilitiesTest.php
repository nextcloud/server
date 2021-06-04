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
namespace OCA\UserStatus\Tests;

use OCA\UserStatus\Capabilities;
use OCA\UserStatus\Service\EmojiService;
use Test\TestCase;

class CapabilitiesTest extends TestCase {

	/** @var EmojiService|\PHPUnit\Framework\MockObject\MockObject */
	private $emojiService;

	/** @var Capabilities */
	private $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->emojiService = $this->createMock(EmojiService::class);
		$this->capabilities = new Capabilities($this->emojiService);
	}

	/**
	 * @param bool $supportsEmojis
	 *
	 * @dataProvider getCapabilitiesDataProvider
	 */
	public function testGetCapabilities(bool $supportsEmojis): void {
		$this->emojiService->expects($this->once())
			->method('doesPlatformSupportEmoji')
			->willReturn($supportsEmojis);

		$this->assertEquals([
			'user_status' => [
				'enabled' => true,
				'supports_emoji' => $supportsEmojis,
			]
		], $this->capabilities->getCapabilities());
	}

	public function getCapabilitiesDataProvider(): array {
		return [
			[true],
			[false],
		];
	}
}
