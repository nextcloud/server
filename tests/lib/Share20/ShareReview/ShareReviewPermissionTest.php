<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\ShareReview\ShareReviewPermission;
use PHPUnit\Framework\TestCase;

final class ShareReviewPermissionTest extends TestCase {

	public function testHoldsAllFields(): void {
		$permission = new ShareReviewPermission(
			id: 'deck:manage',
			displayName: 'Manage board',
			hint: 'Administer participants and board settings',
			priority: 30,
		);

		$this->assertSame('deck:manage', $permission->id);
		$this->assertSame('Manage board', $permission->displayName);
		$this->assertSame('Administer participants and board settings', $permission->hint);
		$this->assertSame(30, $permission->priority);
	}

	public function testDefaults(): void {
		$permission = new ShareReviewPermission(ShareReviewPermission::FILES_READ, 'Read');

		$this->assertSame('files:read', $permission->id);
		$this->assertSame('Read', $permission->displayName);
		$this->assertNull($permission->hint);
		$this->assertSame(50, $permission->priority);
	}

	public function testFilesIdentifiers(): void {
		$this->assertSame('files:read', ShareReviewPermission::FILES_READ);
		$this->assertSame('files:update', ShareReviewPermission::FILES_UPDATE);
		$this->assertSame('files:create', ShareReviewPermission::FILES_CREATE);
		$this->assertSame('files:delete', ShareReviewPermission::FILES_DELETE);
		$this->assertSame('files:reshare', ShareReviewPermission::FILES_RESHARE);
	}
}
