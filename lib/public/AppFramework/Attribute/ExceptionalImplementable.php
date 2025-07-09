<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Attribute;

use Attribute;

/**
 * Attribute to declare that the API marked as Consumable/Listenable/Catchable
 * has an exception and is Implementable/Dispatchable/Throwable by a dedicated
 * app. Changes to such an API have to be communicated to the affected app maintainers.
 *
 * @since 32.0.0
 */
#[Attribute(Attribute::TARGET_ALL | Attribute::IS_REPEATABLE)]
#[Consumable(since: '32.0.0')]
#[Implementable(since: '32.0.0')]
class ExceptionalImplementable {
	public function __construct(
		protected string $app,
		protected ?string $class = null,
	) {
	}

	public function getApp(): string {
		return $this->app;
	}

	public function getClass(): ?string {
		return $this->class;
	}
}
