<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests;

use OCA\UserStatus\Capabilities;
use OCP\IEmojiHelper;
use Test\TestCase;

class CapabilitiesTest extends TestCase {

	/** @var IEmojiHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $emojiHelper;

	/** @var Capabilities */
	private $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->emojiHelper = $this->createMock(IEmojiHelper::class);
		$this->capabilities = new Capabilities($this->emojiHelper);
	}

	/**
	 * @param bool $supportsEmojis
	 *
	 * @dataProvider getCapabilitiesDataProvider
	 */
	public function testGetCapabilities(bool $supportsEmojis): void {
		$this->emojiHelper->expects($this->once())
			->method('doesPlatformSupportEmoji')
			->willReturn($supportsEmojis);

		$this->assertEquals([
			'user_status' => [
				'enabled' => true,
				'restore' => true,
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
