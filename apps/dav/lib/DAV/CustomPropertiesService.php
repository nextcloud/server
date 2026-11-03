<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\DAV;

use OCA\DAV\Db\PropertyMapper;

class CustomPropertiesService {

	public function __construct(
		private readonly PropertyMapper $propertyMapper,
	) {
	}

	/**
	 * Get the properties users have stored on the given paths
	 *
	 * @param array<string, string[]> $paths paths indexed by the user who stored the properties
	 * @return array<string, array<string, array<string, mixed>>> decoded property values indexed by user, path and property name
	 */
	public function getProperties(array $paths): array {
		$formattedPaths = [];
		foreach ($paths as $userId => $userPaths) {
			foreach ($userPaths as $path) {
				$formattedPaths[$userId][CustomPropertiesBackend::formatPath($path)] = $path;
			}
		}
		if ($formattedPaths === []) {
			return [];
		}

		$properties = [];
		foreach ($this->propertyMapper->findPropertiesByPathsAndUsers(array_map('array_keys', $formattedPaths)) as $property) {
			$path = $formattedPaths[$property->getUserid()][$property->getPropertypath()] ?? null;
			if ($path !== null) {
				$properties[$property->getUserid()][$path][$property->getPropertyname()] = CustomPropertiesBackend::decodeValueFromDatabase($property->getPropertyvalue(), (int)$property->getValuetype());
			}
		}

		return $properties;
	}
}
