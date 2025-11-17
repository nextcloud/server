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
	 * @psalm-param string|class-string $alias
	 * @paslm-param string|class-string $target
	 */
	public function __construct(
		string $appId,
		/**
		 * @psalm-var string|class-string
		 */
		private string $alias,
		/**
		 * @psalm-var string|class-string
		 */
		private string $target,
	) {
		parent::__construct($appId);
	}

	/**
	 * @psalm-return string|class-string
	 */
	public function getAlias(): string {
		return $this->alias;
	}

	/**
	 * @psalm-return string|class-string
	 */
	public function getTarget(): string {
		return $this->target;
	}
}
