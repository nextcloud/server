<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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
 *
 */

namespace OC\App\AppStore\Version;

/**
 * Class VersionParser parses the versions as sent by the Nextcloud app store
 *
 * @package OC\App\AppStore
 */
class VersionParser {
	/**
	 * Returns the version for a version string
	 *
	 * @param string $versionSpec
	 * @return Version
	 * @throws \Exception If the version cannot be parsed
	 */
	public function getVersion($versionSpec) {
		// * indicates that the version is compatible with all versions
		if($versionSpec === '*') {
			return new Version('', '');
		}

		// Count the amount of =, if it is one then it's either maximum or minimum
		// version. If it is two then it is maximum and minimum.
		if (preg_match_all('/(?:>|<)(?:=|)[0-9.]+/', $versionSpec, $matches)) {
			switch(count($matches[0])) {
				case 1:
					if(substr($matches[0][0], 0, 1) === '>') {
						return new Version(substr($matches[0][0], 2), '');
					} else {
						return new Version('', substr($matches[0][0], 2));
					}
					break;
				case 2:
					return new Version(substr($matches[0][0], 2), substr($matches[0][1], 2));
					break;
				default:
					throw new \Exception('Version cannot be parsed');
			}
		}

		throw new \Exception('Version cannot be parsed');
	}
}
