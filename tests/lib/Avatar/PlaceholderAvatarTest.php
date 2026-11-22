<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Avatar;

use OC\Avatar\PlaceholderAvatar;
use OC\User\User;
use OCP\Config\IUserConfig;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PlaceholderAvatarTest extends \Test\TestCase {
	private ISimpleFolder&MockObject $folder;
	private User&MockObject $user;
	private IUserConfig&MockObject $userConfig;
	private PlaceholderAvatar $avatar;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->folder = $this->createMock(ISimpleFolder::class);
		$this->user = $this->createMock(User::class);
		$this->user->method('getUID')->willReturn('alice');
		$this->userConfig = $this->createMock(IUserConfig::class);

		$this->avatar = new PlaceholderAvatar(
			$this->folder,
			$this->user,
			$this->createMock(IConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->userConfig,
		);
	}

	public function testRemoveBumpsTheVersion(): void {
		$generated = $this->createMock(ISimpleFile::class);
		$generated->expects($this->once())->method('delete');
		$this->folder->method('getDirectoryListing')->willReturn([$generated]);

		$this->userConfig->expects($this->once())->method('setValueInt')
			->with('alice', 'avatar', 'version', 1);

		$this->avatar->remove();
	}
}
