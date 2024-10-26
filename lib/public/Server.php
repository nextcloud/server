<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class allowing to inject services into your application. You should
 * use whenever possible dependency injections instead.
 *
 * ```php
 * use OCP\Server;
 *
 * $tagManager = Server::get(ITagManager::class);
 * ```
 *
 * @since 25.0.0
 */
final class Server {
	/**
	 * @template T
	 * @param class-string<T>|string $serviceName
	 * @return T|mixed
	 * @psalm-template S as class-string<T>|string
	 * @psalm-param S $serviceName
	 * @psalm-return (S is class-string<T> ? T : mixed)
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @since 25.0.0
	 */
	public static function get(string $serviceName) {
		/** @psalm-suppress UndefinedClass */
		return \OC::$server->get($serviceName);
	}
}
