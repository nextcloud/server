<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share;

use OCP\Files\Folder;

/**
 * Allows defining a IShareProvider with support for the getAllSharesInFolder method.
 *
 * @since 31.0.6
 */
interface IShareProviderSupportsAllSharesInFolder extends IShareProvider {
	/**
	 * Get all shares in a folder.
	 *
	 * @return array<int, list<IShare>>
	 * @since 31.0.6
	 */
	public function getAllSharesInFolder(Folder $node): array;
}
