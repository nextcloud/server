<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Net;

use function filter_var;
use function in_array;
use function strrchr;
use function substr;
use function substr_count;

/**
 * Classifier for network hostnames
 *
 * @internal
 */
class HostnameClassifier {
	private const LOCAL_TOPLEVEL_DOMAINS = [
		'local',
		'localhost',
		'intranet',
		'internal',
		'private',
		'corp',
		'home',
		'lan',
	];

	/**
	 * Check host identifier for local hostname
	 *
	 * IP addresses are not considered local. Use the IpAddressClassifier for those.
	 */
	public function isLocalHostname(string $hostname): bool {
		// Disallow local network top-level domains from RFC 6762
		$topLevelDomain = substr((strrchr($hostname, '.') ?: ''), 1);
		if (in_array($topLevelDomain, self::LOCAL_TOPLEVEL_DOMAINS)) {
			return true;
		}

		// Disallow hostname only
		if (substr_count($hostname, '.') === 0 && !filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			return true;
		}

		return false;
	}
}
