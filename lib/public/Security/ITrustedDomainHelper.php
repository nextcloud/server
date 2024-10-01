<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security;

/**
 * Allows checking domains and full URLs against the list of trusted domains for
 * this server in the config file.
 *
 * @package OCP\Security
 * @since 23.0.0
 */
interface ITrustedDomainHelper {
	/**
	 * Checks whether a given URL is considered as trusted from the list
	 * of trusted domains in the server's config file. If no trusted domains
	 * have been configured and the url is valid, returns true.
	 *
	 * @param string $url
	 * @return bool
	 * @since 23.0.0
	 */
	public function isTrustedUrl(string $url): bool;

	/**
	 * Checks whether a given domain is considered as trusted from the list
	 * of trusted domains in the server's config file. If no trusted domains
	 * have been configured, returns true.
	 * This is used to prevent Host Header Poisoning.
	 *
	 * @param string $domainWithPort
	 * @return bool
	 * @since 23.0.0
	 */
	public function isTrustedDomain(string $domainWithPort): bool;
}
