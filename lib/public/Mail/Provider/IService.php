<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Mail\Provider;

/**
 * Mail Service Interface
 *
 * This interface is a base requirement of methods and functionality used to construct a mail service object
 *
 * @since 30.0.0
 *
 */
interface IService {

	/**
	 * An arbitrary unique text string identifying this service
	 *
	 * @since 30.0.0
	 *
	 * @return string						id of this service (e.g. 1 or service1 or anything else)
	 */
	public function id(): string;

	/**
	 * checks or retrieves what capabilites the service has
	 *
	 * @since 30.0.0
	 *
	 * @param string $ability				required ability e.g. 'MessageSend'
	 *
	 * @return bool|array					true/false if ability is supplied, collection of abilities otherwise
	 */
	public function capable(?string $ability = null): bool | array;

	/**
	 * gets the localized human frendly name of this service
	 *
	 * @since 30.0.0
	 *
	 * @return string						label/name of service (e.g. ACME Company Mail Service)
	 */
	public function getLabel(): string;

	/**
	 * sets the localized human frendly name of this service
	 *
	 * @since 30.0.0
	 *
	 * @param string $value					label/name of service (e.g. ACME Company Mail Service)
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setLabel(string $value): self;

	/**
	 * gets service itentity
	 *
	 * @since 30.0.0
	 *
	 * @return IServiceIdentity				service identity object
	 */
	public function getIdentity(): IServiceIdentity | null;

	/**
	 * sets service identity
	 *
	 * @since 30.0.0
	 *
	 * @param IServiceIdentity $identity	service identity object
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setIdentity(IServiceIdentity $identity): self;

	/**
	 * gets service location
	 *
	 * @since 30.0.0
	 *
	 * @return IServiceLocation				service location object
	 */
	public function getLocation(): IServiceLocation | null;

	/**
	 * sets service location
	 *
	 * @since 30.0.0
	 *
	 * @param IServiceLocation $location	service location object
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setLocation(IServiceLocation $location): self;

	/**
	 * gets the primary mailing address for this service
	 *
	 * @since 30.0.0
	 *
	 * @return IAddress						mail address object
	 */
	public function getPrimaryAddress(): IAddress;

	/**
	 * sets the primary mailing address for this service
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value				mail address object
	 *
	 * @return self                         return this object for command chaining
	 */
	public function setPrimaryAddress(IAddress $value): self;

	/**
	 * gets the secondary mailing addresses (aliases) collection for this service
	 *
	 * @since 30.0.0
	 *
	 * @return array<int, IAddress>			collection of mail address objects
	 */
	public function getSecondaryAddress(): array | null;

	/**
	 * sets the secondary mailing addresses (aliases) for this service
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value				collection of or one or more mail address objects
	 *
	 * @return self                         	return this object for command chaining
	 */
	public function setSecondaryAddress(IAddress ...$value): self;

}
