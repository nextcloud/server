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
	 * @var string
	 * @psalm-var string|class-string
	 */
	private $alias;

	/**
	 * @var string
	 * @psalm-var string|class-string
	 */
	private $target;

	/**
	 * @psalm-param string|class-string $alias
	 * @paslm-param string|class-string $target
	 */
	public function __construct(string $appId,
		string $alias,
		string $target) {
		parent::__construct($appId);
		$this->alias = $alias;
		$this->target = $target;
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
