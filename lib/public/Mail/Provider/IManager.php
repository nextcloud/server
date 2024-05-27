<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Mail\Provider;

/**
 * Mail Provider Manager Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail provider manager object
 *
 * @since 30.0.0
 *
 */
interface IManager {
	
	/**
	 * Register a mail provider
	 *
	 * @since 30.0.0
	 *
	 */
	public function register(IProvider $value): void;

	/**
	 * Determain if any mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return bool
	 */
	public function has(): bool;

	/**
	 * Retrieve a count of how many mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return int
	 */
	public function count(): int;

	/**
	 * Retrieve which mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,String>
	 */
	public function types(): array;

	/**
	 * Retrieve all registered mail providers
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,IProvider>
	 */
	public function providers(): array;

	/**
	 * Retrieve a provider with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @return IProvider|null
	 */
	public function findProviderById(string $id): IProvider | null;

	/**
	 * Retrieve all services for all registered mail providers
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid					user id
	 *
	 * @return array<string,IService>		returns collection of service objects or null if non found
	 */
	public function services(string $uid): array;

	/**
	 * Retrieve a service with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid				user id
	 * @param string $sid				service id
	 * @param string $pid				provider id
	 *
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceById(string $uid, string $sid, ?string $pid = null): IService | null;

	/**
	 * Retrieve a service for a specific mail address
	 * returns first service with specific primary address
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid				user id
	 * @param string $address			mail address (e.g. test@example.com)
	 *
	 * @return IService					returns service object or null if non found
	 */
	public function findServiceByAddress(string $uid, string $address): IService | null;

}
