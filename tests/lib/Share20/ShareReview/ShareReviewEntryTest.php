<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\IShare;
use OCP\Share\ShareReview\ShareReviewEntry;
use OCP\Share\ShareReview\ShareReviewPermission;
use PHPUnit\Framework\TestCase;

final class ShareReviewEntryTest extends TestCase {

	public function testHoldsAllFields(): void {
		$permissions = [
			new ShareReviewPermission('deck:read', 'Read', priority: 80),
			new ShareReviewPermission('deck:manage', 'Manage board', priority: 30),
		];

		$entry = new ShareReviewEntry(
			id: '42',
			object: 'Board "Roadmap"',
			initiator: 'alice',
			type: IShare::TYPE_USER,
			recipient: 'bob',
			lastModifiedTimestamp: 1783764000,
			permissions: $permissions,
			action: 'board-share-42',
			hasPassword: true,
			expirationTimestamp: 1785837600,
			parent: '23',
		);

		$this->assertSame('42', $entry->id);
		$this->assertSame('Board "Roadmap"', $entry->object);
		$this->assertSame('alice', $entry->initiator);
		$this->assertSame(IShare::TYPE_USER, $entry->type);
		$this->assertSame('bob', $entry->recipient);
		$this->assertSame(1783764000, $entry->lastModifiedTimestamp);
		$this->assertSame($permissions, $entry->permissions);
		$this->assertSame('board-share-42', $entry->action);
		$this->assertTrue($entry->hasPassword);
		$this->assertSame(1785837600, $entry->expirationTimestamp);
		$this->assertSame('23', $entry->parent);
	}

	public function testDefaults(): void {
		$entry = new ShareReviewEntry(
			id: '42',
			object: '/folder/file.txt',
			initiator: 'alice',
			type: IShare::TYPE_LINK,
			recipient: 'sToKeN',
			lastModifiedTimestamp: 0,
		);

		$this->assertSame(0, $entry->lastModifiedTimestamp);
		$this->assertSame([], $entry->permissions);
		$this->assertSame('', $entry->action);
		$this->assertFalse($entry->hasPassword);
		$this->assertNull($entry->expirationTimestamp);
		$this->assertNull($entry->parent);
	}
}
