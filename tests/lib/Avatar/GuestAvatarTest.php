<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Avatar;

use OC\Avatar\GuestAvatar;
use OCP\Files\SimpleFS\InMemoryFile;
use PHPUnit\Framework\MockObject\MockObject;
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
	 *
	 * @before
	 * @return void
	 */
	public function setupGuestAvatar() {
		/* @var MockObject|LoggerInterface $logger */
		$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->guestAvatar = new GuestAvatar('einstein', $logger);
	}

	/**
	 * Asserts that testGet() returns the expected avatar.
	 *
	 * For the test a static name "einstein" is used and
	 * the generated image is compared with an expected one.
	 */
	public function testGet(): void {
		$this->markTestSkipped('TODO: Disable because fails on drone');
		$avatar = $this->guestAvatar->getFile(32);
		self::assertInstanceOf(InMemoryFile::class, $avatar);
		$expectedFile = file_get_contents(
			__DIR__ . '/../../data/guest_avatar_einstein_32.png'
		);
		self::assertEquals(trim($expectedFile), trim($avatar->getContent()));
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
