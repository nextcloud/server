<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Provider Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail provider object
 *
 * @since 30.0.0
 *
 */
interface IProvider {

	/**
	 * arbitrary unique text string identifying this provider
	 *
	 * @since 30.0.0
	 *
	 * @return string id of this provider (e.g. UUID or 'IMAP/SMTP' or anything else)
	 */
	public function id(): string;

	/**
	 * localized human friendly name of this provider
	 *
	 * @since 30.0.0
	 *
	 * @return string label/name of this provider (e.g. Plain Old IMAP/SMTP)
	 */
	public function label(): string;

	/**
	 * determine if any services are configured for a specific user
	 *
	 * @since 30.0.0
	 *
	 * @param string $userId user id
	 *
	 * @return bool true if any services are configure for the user
	 */
	public function hasServices(string $userId): bool;

	/**
	 * retrieve collection of services for a specific user
	 *
	 * @param string $userId user id
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,IService> collection of service id and object ['1' => IServiceObject]
	 */
	public function listServices(string $userId): array;

	/**
	 * retrieve a service with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @param string $userId user id
	 * @param string $serviceId service id
	 *
	 * @return IService|null returns service object or null if none found
	 */
	public function findServiceById(string $userId, string $serviceId): ?IService;

	/**
	 * retrieve a service for a specific mail address
	 *
	 * @since 30.0.0
	 *
	 * @param string $userId user id
	 * @param string $address mail address (e.g. test@example.com)
	 *
	 * @return IService|null returns service object or null if none found
	 */
	public function findServiceByAddress(string $userId, string $address): ?IService;

	/**
	 * construct a new empty service object
	 *
	 * @since 30.0.0
	 *
	 * @return IService blank service object
	 */
	public function initiateService(): IService;

}
