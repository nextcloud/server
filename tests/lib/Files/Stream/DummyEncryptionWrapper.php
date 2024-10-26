<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\Stream;

class DummyEncryptionWrapper extends \OC\Files\Stream\Encryption {
	/**
	 * simulate a non-seekable stream wrapper by always return false
	 *
	 * @param int $position
	 * @return bool
	 */
	protected function parentStreamSeek($position) {
		return false;
	}
}
