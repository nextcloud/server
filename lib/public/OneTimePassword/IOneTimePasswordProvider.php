<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\OneTimePassword;

/**
 * @since 35.0.0
 */
interface IOneTimePasswordProvider {

	public function getProviderId(): string;
	public function getName(): string;
	public function getDescription(): string;
	public function getRecipientPattern(): string;
	public function sendOTP(string $recipient, string $password): void;
	public function maskRecipient(string $recipient): string;

}
