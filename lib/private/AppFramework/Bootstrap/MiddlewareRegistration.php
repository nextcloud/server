<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Bootstrap;

use OCP\AppFramework\Middleware;

/**
 * @psalm-immutable
 * @template-extends ServiceRegistration<Middleware>
 */
class MiddlewareRegistration extends ServiceRegistration {
	public function __construct(
		string $appId,
		string $service,
		private bool $global,
	) {
		parent::__construct($appId, $service);
	}

	public function isGlobal(): bool {
		return $this->global;
	}
}
