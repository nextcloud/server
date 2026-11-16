<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

use OCP\ICertificateManager;
use OCP\Server;

/**
 * CA bundle selection for object store connections.
 */
final class CertificateBundle {
	/**
	 * @param bool $useNextcloudBundle use the bundle managed by Nextcloud instead of the system default
	 * @param bool $primaryStorage the connection is used for the primary storage
	 * @return string|null path to the bundle, or null for the system default
	 */
	public static function getPath(bool $useNextcloudBundle, bool $primaryStorage): ?string {
		if (!$useNextcloudBundle) {
			return null;
		}

		$certManager = Server::get(ICertificateManager::class);
		// The absolute bundle is stored on the primary storage, so it cannot be read while setting up the primary storage
		if ($primaryStorage) {
			return $certManager->getDefaultCertificatesBundlePath();
		}
		return $certManager->getAbsoluteBundlePath();
	}
}
