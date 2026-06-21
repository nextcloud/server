<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing;

use OCP\Files\Node;

/**
 * Handles restricting for download of files
 */
class ViewOnly {
	public function isNodeCanBeDownloaded(Node $node): bool {
		// Restrict view-only to nodes which are shared
		$storage = $node->getStorage();
		if (!$storage->instanceOfStorage(SharedStorage::class)) {
			return true;
		}

		// Extract extra permissions
		/** @var SharedStorage $storage */
		$share = $storage->getShare();

		// Check whether download-permission was denied (granted if not set)
		$attributes = $share->getAttributes();
		$canDownload = $attributes?->getAttribute('permissions', 'download');

		return $canDownload !== false;
	}
}
