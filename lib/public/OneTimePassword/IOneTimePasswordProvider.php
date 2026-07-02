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

	/**
	 * A provider ID for registering/selecting it
	 *
	 * @return string the provider ID
	 * @since 35.0.0
	 */
	public function getProviderId(): string;

	/**
	 * A human readable name for the communication method for usage in User Interfaces
	 *
	 * @return string the provider name
	 * @since 35.0.0
	 */
	public function getName(): string;

	/**
	 * A human readable description for usage in User Interfaces
	 *
	 * @return string the description
	 * @since 35.0.0
	 */
	public function getDescription(): string;

	/**
	 * A regex pattern that valid recipients for this provider must match (used in UIs for input validation)
	 *
	 * @return string
	 * @since 35.0.0
	 */
	public function getRecipientPattern(): string;

	/**
	 * Returns a masked version of the recipient identifier (to be shown e.g. in public shares)
	 *
	 * @param string $recipient the unmasked recipient
	 * @return string the masked recipient
	 * @since 35.0.0
	 */
	public function maskRecipient(string $recipient): string;

}
