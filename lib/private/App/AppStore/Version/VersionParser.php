<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Version;

/**
 * Class VersionParser parses the versions as sent by the Nextcloud app store
 *
 * @package OC\App\AppStore
 */
class VersionParser {
	/**
	 * @param string $versionString
	 * @return bool
	 */
	private function isValidVersionString($versionString) {
		return (bool)preg_match('/^[0-9.]+$/', $versionString);
	}

	/**
	 * Returns the version for a version string
	 *
	 * @param string $versionSpec
	 * @return Version
	 * @throws \Exception If the version cannot be parsed
	 */
	public function getVersion($versionSpec) {
		// * indicates that the version is compatible with all versions
		if ($versionSpec === '*') {
			return new Version('', '');
		}

		// Count the amount of =, if it is one then it's either maximum or minimum
		// version. If it is two then it is maximum and minimum.
		$versionElements = explode(' ', $versionSpec);
		$firstVersion = $versionElements[0] ?? '';
		$firstVersionNumber = substr($firstVersion, 2);
		$secondVersion = $versionElements[1] ?? '';
		$secondVersionNumber = substr($secondVersion, 2);

		switch (count($versionElements)) {
			case 1:
				if (!$this->isValidVersionString($firstVersionNumber)) {
					break;
				}
				if (str_starts_with($firstVersion, '>')) {
					return new Version($firstVersionNumber, '');
				}
				return new Version('', $firstVersionNumber);
			case 2:
				if (!$this->isValidVersionString($firstVersionNumber) || !$this->isValidVersionString($secondVersionNumber)) {
					break;
				}
				return new Version($firstVersionNumber, $secondVersionNumber);
		}

		throw new \Exception(
			sprintf(
				'Version cannot be parsed: %s',
				$versionSpec
			)
		);
	}
}
