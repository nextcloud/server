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
class ServiceFactoryRegistration extends ARegistration {
	/**
	 * @var string
	 * @psalm-var string|class-string
	 */
	private $name;

	/**
	 * @var callable
	 * @psalm-var callable(\Psr\Container\ContainerInterface): mixed
	 */
	private $factory;

	/** @var bool */
	private $shared;

	public function __construct(string $appId,
		string $alias,
		callable $target,
		bool $shared) {
		parent::__construct($appId);
		$this->name = $alias;
		$this->factory = $target;
		$this->shared = $shared;
	}

	public function getName(): string {
		return $this->name;
	}

	/**
	 * @psalm-return callable(\Psr\Container\ContainerInterface): mixed
	 */
	public function getFactory(): callable {
		return $this->factory;
	}

	public function isShared(): bool {
		return $this->shared;
	}
}
