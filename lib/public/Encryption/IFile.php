<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Encryption;

/**
 * Interface IFile
 *
 * @since 8.1.0
 */
interface IFile {
	/**
	 * get list of users with access to the file
	 *
	 * @param string $path to the file
	 * @return array
	 * @since 8.1.0
	 */
	public function getAccessList($path);
}
