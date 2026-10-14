<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\Utils;

class PartialFileHelper {

	/**
	 * @return bool true if $path matches the format of a partial upload
	 */
	public static function isPartialFilePath(string $path): bool {
		return preg_match('/\.ocTransferId\d+\.part$/i', $path) === 1;
	}
}
