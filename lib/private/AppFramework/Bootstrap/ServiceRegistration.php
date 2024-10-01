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
	 * @var string
	 * @psalm-var class-string<T>
	 */
	private $service;

	/**
	 * @psalm-param class-string<T> $service
	 */
	public function __construct(string $appId, string $service) {
		parent::__construct($appId);
		$this->service = $service;
	}

	/**
	 * @psalm-return class-string<T>
	 */
	public function getService(): string {
		return $this->service;
	}
}
