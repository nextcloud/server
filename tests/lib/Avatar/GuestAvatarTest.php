<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Avatar;

use OC\Avatar\GuestAvatar;
use OCP\Files\SimpleFS\InMemoryFile;
use OCP\IConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * This class provides test cases for the GuestAvatar class.
 *
 * @package Test\Avatar
 */
class GuestAvatarTest extends TestCase {
	/**
	 * @var GuestAvatar
	 */
	private $guestAvatar;

	/**
	 * Setups a guest avatar instance for tests.
	 */
	#[\PHPUnit\Framework\Attributes\Before()]
	public function setupGuestAvatar(): void {
		/* @var MockObject|LoggerInterface $logger */
		$logger = $this->createMock(LoggerInterface::class);
		$config = $this->createMock(IConfig::class);
		$this->guestAvatar = new GuestAvatar('einstein', $config, $logger);
	}

	public static function themeProvider(): array {
		return [
			'light theme' => ['darkTheme' => false, 'backgroundColor' => 0xe9f3f7],
			'dark theme' => ['darkTheme' => true, 'backgroundColor' => 0x030e12],
		];
	}

	/**
	 * Asserts that GuestAvatar::getFile() returns the expected avatar.
	 *
	 * For the test a static name "einstein" is used.
	 */
	#[DataProvider('themeProvider')]
	public function testGet(bool $darkTheme, int $backgroundColor): void {
		$size = 32;
		$avatar = $this->guestAvatar->getFile($size, $darkTheme);
		self::assertInstanceOf(InMemoryFile::class, $avatar);
		$content = $avatar->getContent();
		self::assertStringStartsWith("\x89PNG\r\n\x1a\n", $content, 'The avatar is not a PNG');

		$image = imagecreatefromstring($content);
		self::assertInstanceOf(\GdImage::class, $image);
		self::assertSame($size, imagesx($image));
		self::assertSame($size, imagesy($image));
		self::assertSame($backgroundColor, imagecolorat($image, 0, 0), 'The background is not the guest color');

		// The anti-aliased edges depend on the GD and FreeType versions, the strokes of the letter don't
		$letterPixels = 0;
		for ($y = 0; $y < $size; $y++) {
			for ($x = 0; $x < $size; $x++) {
				if (imagecolorat($image, $x, $y) === 0x248eb5) {
					$letterPixels++;
				}
			}
		}
		// A Noto Sans "E" at 32 px has 27 fully covered pixels
		self::assertSame(27, $letterPixels, 'The avatar does not show a Noto Sans "E" in the guest color');
	}

	/**
	 * Asserts that "testIsCustomAvatar" returns false for guests.
	 *
	 * @return void
	 */
	public function testIsCustomAvatar(): void {
		self::assertFalse($this->guestAvatar->isCustomAvatar());
	}
}
