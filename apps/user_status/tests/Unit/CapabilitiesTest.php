<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests;

use OCA\UserStatus\Capabilities;
use OCP\IEmojiHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CapabilitiesTest extends TestCase {
	private IEmojiHelper&MockObject $emojiHelper;
	private Capabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->emojiHelper = $this->createMock(IEmojiHelper::class);
		$this->capabilities = new Capabilities($this->emojiHelper);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'getCapabilitiesDataProvider')]
	public function testGetCapabilities(bool $supportsEmojis): void {
		$this->emojiHelper->expects($this->once())
			->method('doesPlatformSupportEmoji')
			->willReturn($supportsEmojis);

		$this->assertEquals([
			'user_status' => [
				'enabled' => true,
				'restore' => true,
				'supports_emoji' => $supportsEmojis,
				'supports_busy' => true,
			]
		], $this->capabilities->getCapabilities());
	}

	public static function getCapabilitiesDataProvider(): array {
		return [
			[true],
			[false],
		];
	}
}
