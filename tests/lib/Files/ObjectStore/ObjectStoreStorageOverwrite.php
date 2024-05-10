<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OCP\Files\ObjectStore\IObjectStore;

/**
 * Allow overwriting the object store instance for test purposes
 */
class ObjectStoreStorageOverwrite extends ObjectStoreStorage {
	public function setObjectStore(IObjectStore $objectStore): void {
		$this->objectStore = $objectStore;
	}

	public function getObjectStore(): IObjectStore {
		return $this->objectStore;
	}

	public function setValidateWrites(bool $validate): void {
		$this->validateWrites = $validate;
	}
}
