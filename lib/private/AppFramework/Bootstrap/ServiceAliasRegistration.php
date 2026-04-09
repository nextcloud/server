<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

/**
 * @psalm-immutable
 */
class ServiceAliasRegistration extends ARegistration {
	/**
	 * @param class-string $alias
	 * @param class-string $target
	 */
	public function __construct(
		string $appId,
		private readonly string $alias,
		private readonly string $target,
	) {
		parent::__construct($appId);
	}

	/**
	 * @return class-string
	 */
	public function getAlias(): string {
		return $this->alias;
	}

	/**
	 * @return class-string
	 */
	public function getTarget(): string {
		return $this->target;
	}
}
