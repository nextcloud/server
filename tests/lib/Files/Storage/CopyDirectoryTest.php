<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\Storage;

use OC\Files\Storage\PolyFill\CopyDirectory;
use OC\Files\Storage\Temporary;

class StorageNoRecursiveCopy extends Temporary {
	public function copy(string $source, string $target): bool {
		if ($this->is_dir($source)) {
			return false;
		}
		return copy($this->getSourcePath($source), $this->getSourcePath($target));
	}
}

class CopyDirectoryStorage extends StorageNoRecursiveCopy {
	use CopyDirectory;
}

/**
 * Class CopyDirectoryTest
 *
 * @group DB
 *
 * @package Test\Files\Storage
 */
class CopyDirectoryTest extends Storage {
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new CopyDirectoryStorage([]);
	}
}
