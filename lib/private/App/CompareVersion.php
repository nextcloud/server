<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App;

use InvalidArgumentException;
use function explode;

class CompareVersion {
	private const REGEX_MAJOR = '/^\d+$/';
	private const REGEX_MAJOR_MINOR = '/^\d+\.\d+$/';
	private const REGEX_MAJOR_MINOR_PATCH = '/^\d+\.\d+\.\d+(?!\.\d+)/';
	private const REGEX_ACTUAL = '/^\d+(\.\d+){1,2}/';

	/**
	 * Checks if the given server version fulfills the given (app) version requirements.
	 *
	 * Version requirements can be 'major.minor.patch', 'major.minor' or just 'major',
	 * so '13.0.1', '13.0' and '13' are valid.
	 *
	 * @param string $actual version as major.minor.patch notation
	 * @param string $required version where major is required and minor and patch are optional
	 * @param string $comparator passed to `version_compare`
	 * @return bool whether the requirement is fulfilled
	 * @throws InvalidArgumentException if versions specified in an invalid format
	 */
	public function isCompatible(string $actual, string $required,
		string $comparator = '>='): bool {
		if (!preg_match(self::REGEX_ACTUAL, $actual, $matches)) {
			throw new InvalidArgumentException("version specification $actual is invalid");
		}
		$cleanActual = $matches[0];

		if (preg_match(self::REGEX_MAJOR, $required) === 1) {
			return $this->compareMajor($cleanActual, $required, $comparator);
		} elseif (preg_match(self::REGEX_MAJOR_MINOR, $required) === 1) {
			return $this->compareMajorMinor($cleanActual, $required, $comparator);
		} elseif (preg_match(self::REGEX_MAJOR_MINOR_PATCH, $required) === 1) {
			return $this->compareMajorMinorPatch($cleanActual, $required, $comparator);
		} else {
			throw new InvalidArgumentException("required version $required is invalid");
		}
	}

	private function compareMajor(string $actual, string $required,
		string $comparator) {
		$actualMajor = explode('.', $actual)[0];
		$requiredMajor = explode('.', $required)[0];

		return version_compare($actualMajor, $requiredMajor, $comparator);
	}

	private function compareMajorMinor(string $actual, string $required,
		string $comparator) {
		$actualMajor = explode('.', $actual)[0];
		$actualMinor = explode('.', $actual)[1];
		$requiredMajor = explode('.', $required)[0];
		$requiredMinor = explode('.', $required)[1];

		return version_compare("$actualMajor.$actualMinor",
			"$requiredMajor.$requiredMinor", $comparator);
	}

	private function compareMajorMinorPatch($actual, $required, $comparator) {
		$actualMajor = explode('.', $actual)[0];
		$actualMinor = explode('.', $actual)[1];
		$actualPatch = explode('.', $actual)[2];
		$requiredMajor = explode('.', $required)[0];
		$requiredMinor = explode('.', $required)[1];
		$requiredPatch = explode('.', $required)[2];

		return version_compare("$actualMajor.$actualMinor.$actualPatch",
			"$requiredMajor.$requiredMinor.$requiredPatch", $comparator);
	}
}
