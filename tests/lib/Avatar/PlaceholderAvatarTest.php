<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Avatar;

use OC\Avatar\AvatarVersion;
use OC\Avatar\PlaceholderAvatar;
use OC\User\User;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PlaceholderAvatarTest extends \Test\TestCase {
	private ISimpleFolder&MockObject $folder;
	private User&MockObject $user;
	private AvatarVersion&MockObject $avatarVersion;
	private PlaceholderAvatar $avatar;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->folder = $this->createMock(ISimpleFolder::class);
		$this->user = $this->createMock(User::class);
		$this->user->method('getUID')->willReturn('alice');
		$this->avatarVersion = $this->createMock(AvatarVersion::class);

		$this->avatar = new PlaceholderAvatar(
			$this->folder,
			$this->user,
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->avatarVersion,
		);
	}

	public function testRemoveBumpsTheVersion(): void {
		$generated = $this->createMock(ISimpleFile::class);
		$generated->expects($this->once())->method('delete');
		$this->folder->method('getDirectoryListing')->willReturn([$generated]);

		$this->avatarVersion->expects($this->once())->method('bump')->with('alice');

		$this->avatar->remove();
	}
}
