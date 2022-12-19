<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
	 *
	 * @param string $hostname
	 *
	 * @return bool
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
