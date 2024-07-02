<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @return array<string,array<string,IService>>		returns collection of service objects or null if non found
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
	 * @param string $pid				provider id
	 *
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceByAddress(string $uid, string $address, ?string $pid = null): IService | null;

}
