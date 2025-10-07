<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Mount;

use OC\Files\Storage\FailedStorage;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;

class DummyMountPoint implements IMountPoint {
	public function getMountPoint() {
		return '';
	}

	public function setMountPoint($mountPoint) {
	}

	public function getStorage(): IStorage {
		return new FailedStorage(['exception' => new \LogicException('Dummy storage') ]);
	}

	public function getStorageId(): string {
		return '';
	}

	public function getNumericStorageId() {
		return -1;
	}

	public function getInternalPath($path) {
		return $path;
	}

	public function wrapStorage($wrapper) {
	}

	public function getOption($name, $default) {
		if ($name === 'previews') {
			return false;
		}
		return $default;
	}

	public function getOptions(): array {
		return ['previews' => false];
	}

	public function getStorageRootId(): int {
		return -1;
	}

	public function getMountId() {
		return null;
	}

	public function getMountType(): string {
		return 'dummy';
	}

	public function getMountProvider(): string {
		return '';
	}
}
