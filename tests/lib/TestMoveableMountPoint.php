<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\Files\Mount\MountPoint;
use OCP\Files\Mount\IMovableMount;
use Override;

/**
 * Test moveable mount for mocking
 */
class TestMoveableMountPoint extends MountPoint implements IMovableMount {
	#[Override]
	public function moveMount(string $target): bool {
		$this->setMountPoint($target);
		return true;
	}

	#[Override]
	public function removeMount(): bool {
		return false;
	}
}
