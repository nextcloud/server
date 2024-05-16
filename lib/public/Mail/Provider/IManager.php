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
 * API surface for apps interacting with and making use of Mail providers
 * without knowing which providers are installed
 * 
 * @since 30.0.0
 */
interface IManager {
	
	/**
	 * Register a mail provider
	 * 
	 * @since 30.0.0
	 */
	public function register($provider): void;

	/**
	 * Determain if any mail providers are registered
	 * 
	 * @since 30.0.0
	 */
	public function has(): bool;

	/**
	 * Retrieve a count of how many mail providers are registered
	 * 
	 * @since 30.0.0
	 */
	public function count(): int;

	/**
	 * Retrieve which mail providers are registered
	 * 
	 * @since 30.0.0
	 */
	public function types(): array;

	/**
	 * Retrieve all registered mail providers
	 * 
	 * @since 30.0.0
	 */
	public function providers(): array;

	/**
	 * Retrieve all services for all registered mail providers
	 * 
	 * @since 30.0.0
	 */
	public function services(string $uid): array;

	/**
	 * find a specific service for an address
	 * 
	 * @since 30.0.0
	 */
	public function findService(string $uid, string $address);

}
