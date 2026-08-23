<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

/**
 * Orders matching preview providers for a MIME type.
 *
 * MIME overrides run first (still skipping globally disabled providers), then
 * remaining enabled providers in `enabledPreviewProviders` order. A MIME deny
 * list removes providers entirely for that type. An empty override does not
 * replace the global list.
 */
class ProviderPriorityResolver {
	public function __construct(
		private readonly PreviewAdminConfig $adminConfig,
	) {
	}

	/**
	 * @param list<string> $matchingClasses Provider classes that already match the MIME
	 * @return list<string>
	 */
	public function sortMatchingProviders(string $mimeType, array $matchingClasses): array {
		$mimeType = strtolower($mimeType);
		$matching = [];
		foreach ($matchingClasses as $class) {
			if (!is_string($class) || $class === '') {
				continue;
			}
			$matching[] = PreviewAdminConfig::normalizeClassName($class);
		}
		$matching = array_values(array_unique($matching));
		if ($matching === []) {
			return [];
		}

		$matchingSet = array_fill_keys($matching, true);
		$enabled = $this->adminConfig->getEnabledPreviewProviders();
		$enabledSet = array_fill_keys($enabled, true);
		$overrides = $this->adminConfig->getMimePriority()[$mimeType] ?? [];
		$denied = array_fill_keys($this->adminConfig->getMimeDeny()[$mimeType] ?? [], true);

		$ordered = [];

		foreach ($overrides as $class) {
			if (!isset($matchingSet[$class], $enabledSet[$class]) || isset($denied[$class])) {
				continue;
			}
			$ordered[] = $class;
			unset($matchingSet[$class]);
		}

		foreach ($enabled as $class) {
			if (!isset($matchingSet[$class]) || isset($denied[$class])) {
				continue;
			}
			$ordered[] = $class;
			unset($matchingSet[$class]);
		}

		// Bootstrap / otherwise registered providers that are not in the global list
		foreach ($matching as $class) {
			if (!isset($matchingSet[$class]) || isset($denied[$class])) {
				continue;
			}
			$ordered[] = $class;
		}

		return $ordered;
	}
}
