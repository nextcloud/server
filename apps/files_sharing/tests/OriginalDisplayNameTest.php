<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests;

use OCA\Files_Sharing\OriginalDisplayName;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

class OriginalDisplayNameTest extends \Test\TestCase {
	private IShare&MockObject $share;

	protected function setUp(): void {
		parent::setUp();
		$this->share = $this->createMock(IShare::class);
	}

	public function testReturnsNullWhenTheNodeIsNotTheShareRoot(): void {
		$this->share->expects($this->never())->method('getNode');

		$this->assertNull(OriginalDisplayName::forShare($this->share, 'Project-A', false));
	}

	public function testReturnsNullWhenTheNamesMatch(): void {
		$this->givenSourceName('Project-A');

		$this->assertNull(OriginalDisplayName::forShare($this->share, 'Project-A', true));
	}

	public function testReturnsTheSourceNameWhenTheRecipientRenamedTheShare(): void {
		$this->givenSourceName('Project-A');

		$this->assertSame(
			'Project-A',
			OriginalDisplayName::forShare($this->share, 'Project-A-with-Alice', true),
		);
	}

	public function testReturnsTheSourceNameForAnAutomaticConflictSuffix(): void {
		$this->givenSourceName('Project-A');

		$this->assertSame(
			'Project-A',
			OriginalDisplayName::forShare($this->share, 'Project-A (1)', true),
		);
	}

	public function testUsesTheShareNodeWhenNoCacheEntryIsAvailable(): void {
		$node = $this->createMock(Node::class);
		$node->method('getName')->willReturn('Project-A');
		$this->share->method('getNodeCacheEntry')->willReturn(null);
		$this->share->method('getNode')->willReturn($node);

		$this->assertSame(
			'Project-A',
			OriginalDisplayName::forShare($this->share, 'Project-A-with-Alice', true),
		);
	}

	public function testReturnsNullWhenTheSourceCannotBeResolved(): void {
		$this->share->method('getNodeCacheEntry')->willReturn(null);
		$this->share->method('getNode')->willThrowException(new NotFoundException());

		$this->assertNull(OriginalDisplayName::forShare($this->share, 'Project-A-with-Alice', true));
	}

	private function givenSourceName(string $name): void {
		$cacheEntry = $this->createMock(ICacheEntry::class);
		$cacheEntry->method('getName')->willReturn($name);
		$this->share->method('getNodeCacheEntry')->willReturn($cacheEntry);
		$this->share->expects($this->never())->method('getNode');
	}
}
