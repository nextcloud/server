<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Middleware;

use NCU\Sharing\ISharingManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSException;
use OCP\Server;

final class ShareApiEnabledMiddleware extends Middleware {
	#[\Override]
	public function beforeController(Controller $controller, string $methodName): void {
		if (!Server::get(ISharingManager::class)->isApiEnabled()) {
			throw new OCSException('The Unified Sharing API is not enabled.', Http::STATUS_NOT_IMPLEMENTED);
		}
	}
}
