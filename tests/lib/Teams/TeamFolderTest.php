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
		$this->assertNull($folder->getQuota());
		$this->assertNull($folder->getRootFileId());
		$this->assertSame(['id' => 42, 'quota' => null, 'mountPoint' => 'Engineering', 'rootFileId' => null], $folder->jsonSerialize());
	}

	public function testSerializesFolderQuota(): void {
		$folder = new TeamFolder(42, 'Engineering', 1024);

		$this->assertSame(1024, $folder->getQuota());
		$this->assertSame(['id' => 42, 'quota' => 1024, 'mountPoint' => 'Engineering', 'rootFileId' => null], $folder->jsonSerialize());
	}

	public function testSerializesFolderRootFileId(): void {
		$folder = new TeamFolder(42, 'Engineering', 1024, 1337);

		$this->assertSame(1337, $folder->getRootFileId());
		$this->assertSame(['id' => 42, 'quota' => 1024, 'mountPoint' => 'Engineering', 'rootFileId' => 1337], $folder->jsonSerialize());
	}
}
