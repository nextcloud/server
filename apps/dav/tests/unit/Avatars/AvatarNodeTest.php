<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\Unit\Avatars;

use OCA\DAV\Avatars\AvatarNode;
use OCP\IAvatar;
use Test\TestCase;

class AvatarNodeTest extends TestCase {
	public function testGetName(): void {
		/** @var IAvatar | \PHPUnit\Framework\MockObject\MockObject $a */
		$a = $this->createMock(IAvatar::class);
		$n = new AvatarNode(1024, 'png', $a);
		$this->assertEquals('1024.png', $n->getName());
	}

	public function testGetContentType(): void {
		/** @var IAvatar | \PHPUnit\Framework\MockObject\MockObject $a */
		$a = $this->createMock(IAvatar::class);
		$n = new AvatarNode(1024, 'png', $a);
		$this->assertEquals('image/png', $n->getContentType());

		$n = new AvatarNode(1024, 'jpeg', $a);
		$this->assertEquals('image/jpeg', $n->getContentType());
	}
}
