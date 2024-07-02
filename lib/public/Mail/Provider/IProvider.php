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
	 * An arbitrary unique text string identifying this provider
	 *
	 * @since 30.0.0
	 *
	 * @return string				id of this provider (e.g. UUID or 'IMAP/SMTP' or anything else)
	 */
	public function id(): string;

	/**
	 * The localized human frendly name of this provider
	 *
	 * @since 30.0.0
	 *
	 * @return string				label/name of this provider (e.g. Plain Old IMAP/SMTP)
	 */
	public function label(): string;

	/**
	 * Determain if any services are configured for a specific user
	 *
	 * @since 30.0.0
	 *
	 * @return bool 				true if any services are configure for the user
	 */
	public function hasServices(string $uid): bool;

	/**
	 * retrieve collection of services for a specific user
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,IService>		collection of service objects
	 */
	public function listServices(string $uid): array;

	/**
	 * Retrieve a service with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid				user id
	 * @param string $id				service id
	 *
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceById(string $uid, string $id): IService | null;

	/**
	 * Retrieve a service for a specific mail address
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid				user id
	 * @param string $address			mail address (e.g. test@example.com)
	 *
	 * @return IService					returns service object or null if non found
	 */
	public function findServiceByAddress(string $uid, string $address): IService | null;

	/**
	 * create a service configuration for a specific user
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid			user id of user to configure service for
	 * @param IService $service 	service configuration object
	 *
	 * @return string				id of created service
	 */
	public function createService(string $uid, IService $service): string;

	/**
	 * modify a service configuration for a specific user
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid			user id of user to configure service for
	 * @param IService $service 	service configuration object
	 *
	 * @return string				id of modifided service
	 */
	public function modifyService(string $uid, IService $service): string;

	/**
	 * delete a service configuration for a specific user
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid			user id of user to delete service for
	 * @param IService $service 	service configuration object
	 *
	 * @return bool					status of delete action
	 */
	public function deleteService(string $uid, IService $service): bool;

}
