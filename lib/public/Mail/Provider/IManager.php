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
	 * determine if any mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return bool
	 */
	public function has(): bool;

	/**
	 * retrieve a count of how many mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return int
	 */
	public function count(): int;

	/**
	 * retrieve which mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,String> collection of provider id and label ['jmap' => 'JMap Connector']
	 */
	public function types(): array;

	/**
	 * retrieve all registered mail providers
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,IProvider> collection of provider id and object ['jmap' => IProviderObject]
	 */
	public function providers(): array;

	/**
	 * retrieve a provider with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @param string $providerId provider id
	 *
	 * @return IProvider|null
	 */
	public function findProviderById(string $providerId): ?IProvider;

	/**
	 * retrieve all services for all registered mail providers
	 *
	 * @since 30.0.0
	 *
	 * @param string $userId user id
	 *
	 * @return array<string,array<string,IService>> collection of provider id, service id and object ['jmap' => ['Service1' => IServiceObject]]
	 */
	public function services(string $userId): array;

	/**
	 * retrieve a service with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @param string $userId user id
	 * @param string $serviceId service id
	 * @param string $providerId provider id
	 *
	 * @return IService|null returns service object or null if none found
	 */
	public function findServiceById(string $userId, string $serviceId, ?string $providerId = null): ?IService;

	/**
	 * retrieve a service for a specific mail address
	 * returns first service with specific primary address
	 *
	 * @since 30.0.0
	 *
	 * @param string $userId user id
	 * @param string $address mail address (e.g. test@example.com)
	 * @param string $providerId provider id
	 *
	 * @return IService|null returns service object or null if none found
	 */
	public function findServiceByAddress(string $userId, string $address, ?string $providerId = null): ?IService;

}
