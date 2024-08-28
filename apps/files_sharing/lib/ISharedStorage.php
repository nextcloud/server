<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing;

use OCP\Files\Storage\IStorage;

/**
 * @deprecated 30.0.0 use `\OCP\Files\Storage\ISharedStorage` instead
 */
interface ISharedStorage extends IStorage {
}
