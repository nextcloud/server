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
	private bool $global;

	public function __construct(string $appId, string $service, bool $global) {
		parent::__construct($appId, $service);
		$this->global = $global;
	}

	public function isGlobal(): bool {
		return $this->global;
	}
}
