<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Bootstrap;

use OCP\AppFramework\Attribute\Consumable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @since 20.0.0
 */
#[Consumable(since: '20.0.0')]
interface IBootContext {
	/**
	 * Get hold of the app's container
	 *
	 * Useful to register and query app-specific services
	 *
	 * @since 20.0.0
	 * @since 35.0.0 Typed as returning a ContainerInterface instead of the deprecated IAppContainer
	 */
	public function getAppContainer(): ContainerInterface;

	/**
	 * Get hold of the server DI container
	 *
	 * Useful to register and query system-wide services
	 *
	 * @since 20.0.0
	 * @since 35.0.0 Typed as returning a ContainerInterface instead of the deprecated IServerContainer
	 */
	public function getServerContainer(): ContainerInterface;

	/**
	 * Invoke the given callable and inject all parameters based on their types
	 * and names
	 *
	 * Note: when used with methods, make sure they are public or use \Closure::fromCallable
	 * to wrap the private method call, e.g.
	 *  * `$context->injectFn([$obj, 'publicMethod'])`
	 *  * `$context->injectFn([$this, 'publicMethod'])`
	 *  * `$context->injectFn(\Closure::fromCallable([$this, 'privateMethod']))`
	 *
	 * Note: the app container will be queried
	 *
	 * @param callable $fn
	 * @throws ContainerExceptionInterface if at least one of the parameter can't be resolved
	 * @throws Throwable any error the function invocation might cause
	 * @return mixed|null the return value of the invoked function, if any
	 * @since 20.0.0
	 */
	public function injectFn(callable $fn);
}
