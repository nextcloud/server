<?php

declare(strict_types=1);

/**
 * @copyright Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
