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
			permissions: 31,
			time: '2026-07-07 12:00:00',
			action: 'board-share-42',
			timestamp: 1783764000,
			password: true,
			expiration: '2026-08-01',
			parent: '23',
		);

		$this->assertSame('42', $entry->id);
		$this->assertSame('Board "Roadmap"', $entry->object);
		$this->assertSame('alice', $entry->initiator);
		$this->assertSame(IShare::TYPE_USER, $entry->type);
		$this->assertSame('bob', $entry->recipient);
		$this->assertSame(31, $entry->permissions);
		$this->assertSame('2026-07-07 12:00:00', $entry->time);
		$this->assertSame('board-share-42', $entry->action);
		$this->assertSame(1783764000, $entry->timestamp);
		$this->assertTrue($entry->password);
		$this->assertSame('2026-08-01', $entry->expiration);
		$this->assertSame('23', $entry->parent);
	}

	public function testDefaults(): void {
		$entry = new ShareReviewEntry(
			id: '42',
			object: '/folder/file.txt',
			initiator: 'alice',
			type: IShare::TYPE_LINK,
			recipient: 'sToKeN',
		);

		$this->assertSame(1, $entry->permissions);
		$this->assertSame('1970-01-01 01:00:00', $entry->time);
		$this->assertSame('', $entry->action);
		$this->assertNull($entry->timestamp);
		$this->assertFalse($entry->password);
		$this->assertNull($entry->expiration);
		$this->assertNull($entry->parent);
	}
}
