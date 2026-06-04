<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

use Closure;
use OCP\AppFramework\QueryException;
use Psr\Container\ContainerInterface;
use ReflectionFunction;
use ReflectionParameter;
use function array_map;

class FunctionInjector {
	public function __construct(
		private ContainerInterface $container,
	) {
	}

	public function injectFn(callable $fn) {
		$reflected = new ReflectionFunction(Closure::fromCallable($fn));
		return $fn(...array_map(function (ReflectionParameter $param) {
			// First we try by type (more likely these days)
			if (($type = $param->getType()) !== null) {
				try {
					return $this->container->get($type->getName());
				} catch (QueryException $ex) {
					// Ignore and try name as well
				}
			}
			// Second we try by name (mostly for primitives)
			try {
				return $this->container->get($param->getName());
			} catch (QueryException $ex) {
				// As a last resort we pass `null` if allowed
				if ($type !== null && $type->allowsNull()) {
					return null;
				}

				// Nothing worked, time to bail out
				throw $ex;
			}
		}, $reflected->getParameters()));
	}
}
