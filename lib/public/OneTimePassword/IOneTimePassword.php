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

	public function setId(int $id): self;

	public function getId(): int;

	public function setPassword(?string $password): self;

	public function getPassword(): ?string;

	public function setRecipient(string $recipient): self;

	public function getRecipient(): string;

	public function getProviderId(): string;

	public function setProviderId(string $provider): self;

	public function setExpirationTime(?\DateTime $expiration): self;

	public function getExpirationTime(): ?\DateTime;
}
