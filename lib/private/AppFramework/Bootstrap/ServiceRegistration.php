<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

/**
 * @psalm-immutable
 * @template T
 */
class ServiceRegistration extends ARegistration {
	/**
	 * @psalm-param class-string<T> $service
	 */
	public function __construct(
		string $appId,
		private string $service,
	) {
		parent::__construct($appId);
	}

	/**
	 * @psalm-return class-string<T>
	 */
	public function getService(): string {
		return $this->service;
	}
}
