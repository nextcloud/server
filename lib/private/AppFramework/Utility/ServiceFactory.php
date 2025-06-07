<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\AppFramework\Utility;

use Psr\Container\ContainerInterface;

class ServiceFactory {
	private $factory;
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container, callable $factory) {
		$this->container = $container;
		$this->factory = $factory;
	}

	public function get() {
		return ($this->factory)($this->container);
	}
}
