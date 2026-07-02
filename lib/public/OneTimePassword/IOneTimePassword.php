<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\OneTimePassword;

use OCP\AppFramework\Attribute\Consumable;

/**
 * An interface representing an OTP configuration
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IOneTimePassword {

	/**
	 * Set the OTP id
	 *
	 * @param int $id the id
	 * @return self
	 * @since 35.0.0
	 */
	public function setId(int $id): self;

	/**
	 * Get the OTP id
	 *
	 * @return int the id
	 * @since 35.0.0
	 */
	public function getId(): int;

	/**
	 * Set the OTP password
	 *
	 * @param string|null $password the password or null
	 * @return self
	 * @since 35.0.0
	 */
	public function setPassword(?string $password): self;

	/**
	 * Get the OTP password
	 *
	 * @return string|null the password or null
	 * @since 35.0.0
	 */
	public function getPassword(): ?string;

	/**
	 * Set the OTP recipient
	 *
	 * @param string $recipient the recipient identifier
	 * @return self
	 * @since 35.0.0
	 */
	public function setRecipient(string $recipient): self;

	/**
	 * Get the OTP recipient
	 *
	 * @return string the recipient identifier
	 * @since 35.0.0
	 */
	public function getRecipient(): string;

	/**
	 * Set the OTP provider
	 *
	 * @return string the provider id
	 * @since 35.0.0
	 */
	public function getProviderId(): string;

	/**
	 * Set the OTP provider
	 *
	 * @param string $provider the provider id
	 * @return self
	 * @since 35.0.0
	 */
	public function setProviderId(string $provider): self;

	/**
	 * Set the OTP expiration time
	 *
	 * @param \DateTime|null $expiration the expiration time or null
	 * @return self
	 * @since 35.0.0
	 */
	public function setExpirationTime(?\DateTime $expiration): self;

	/**
	 * Get the OTP expiration time
	 *
	 * @return \DateTime|null the expiration time or null
	 * @since 35.0.0
	 */
	public function getExpirationTime(): ?\DateTime;
}
