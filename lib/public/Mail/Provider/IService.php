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
	 * arbitrary unique text string identifying this service
	 *
	 * @since 30.0.0
	 *
	 * @return string id of this service (e.g. 1 or service1 or anything else)
	 */
	public function id(): string;

	/**
	 * checks if a service is able of performing an specific action
	 *
	 * @since 30.0.0
	 *
	 * @param string $value required ability e.g. 'MessageSend'
	 *
	 * @return bool true/false if ability is supplied and found in collection
	 */
	public function capable(string $value): bool;

	/**
	 * retrieves a collection of what actions a service can perfrom
	 *
	 * @since 30.0.0
	 *
	 * @return array collection of abilities otherwise empty collection
	 */
	public function capabilities(): array;

	/**
	 * gets the localized human frendly name of this service
	 *
	 * @since 30.0.0
	 *
	 * @return string label/name of service (e.g. ACME Company Mail Service)
	 */
	public function getLabel(): string;

	/**
	 * sets the localized human frendly name of this service
	 *
	 * @since 30.0.0
	 *
	 * @param string $value label/name of service (e.g. ACME Company Mail Service)
	 *
	 * @return self return this object for command chaining
	 */
	public function setLabel(string $value): self;

	/**
	 * gets the primary mailing address for this service
	 *
	 * @since 30.0.0
	 *
	 * @return IAddress mail address object
	 */
	public function getPrimaryAddress(): IAddress;

	/**
	 * sets the primary mailing address for this service
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress $value mail address object
	 *
	 * @return self return this object for command chaining
	 */
	public function setPrimaryAddress(IAddress $value): self;

	/**
	 * gets the secondary mailing addresses (aliases) collection for this service
	 *
	 * @since 30.0.0
	 *
	 * @return array<int, IAddress> collection of mail address objects
	 */
	public function getSecondaryAddresses(): array;

	/**
	 * sets the secondary mailing addresses (aliases) for this service
	 *
	 * @since 30.0.0
	 *
	 * @param IAddress ...$value collection of one or more mail address objects
	 *
	 * @return self return this object for command chaining
	 */
	public function setSecondaryAddresses(IAddress ...$value): self;

	/**
	 * construct a new empty message object
	 *
	 * @since 30.0.0
	 *
	 * @return IMessage blank message object
	 */
	public function initiateMessage(): IMessage;

}
