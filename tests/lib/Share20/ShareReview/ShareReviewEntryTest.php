<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\IShare;
use OCP\Share\ShareReview\ShareReviewEntry;
use PHPUnit\Framework\TestCase;

final class ShareReviewEntryTest extends TestCase {

	public function testHoldsAllFields(): void {
		$entry = new ShareReviewEntry(
			id: '42',
			object: 'Board "Roadmap"',
			initiator: 'alice',
			type: IShare::TYPE_USER,
			recipient: 'bob',
			lastModifiedTimestamp: 1783764000,
			permissions: 31,
			action: 'board-share-42',
			hasPassword: true,
			canManage: true,
			expirationTimestamp: 1785837600,
			parent: '23',
		);

		$this->assertSame('42', $entry->id);
		$this->assertSame('Board "Roadmap"', $entry->object);
		$this->assertSame('alice', $entry->initiator);
		$this->assertSame(IShare::TYPE_USER, $entry->type);
		$this->assertSame('bob', $entry->recipient);
		$this->assertSame(1783764000, $entry->lastModifiedTimestamp);
		$this->assertSame(31, $entry->permissions);
		$this->assertSame('board-share-42', $entry->action);
		$this->assertTrue($entry->hasPassword);
		$this->assertTrue($entry->canManage);
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
		$this->assertSame(1, $entry->permissions);
		$this->assertSame('', $entry->action);
		$this->assertFalse($entry->hasPassword);
		$this->assertFalse($entry->canManage);
		$this->assertNull($entry->expirationTimestamp);
		$this->assertNull($entry->parent);
	}
}
