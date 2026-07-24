<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Teams;

use OCP\Teams\TeamFolder;
use Test\TestCase;

class TeamFolderTest extends TestCase {
	public function testSerializesFolderIdentity(): void {
		$folder = new TeamFolder(42, 'Engineering');

		$this->assertSame(42, $folder->getId());
		$this->assertSame('Engineering', $folder->getMountPoint());
		$this->assertSame(['id' => 42, 'mountPoint' => 'Engineering'], $folder->jsonSerialize());
	}
}
