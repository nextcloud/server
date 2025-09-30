<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Model;

use OCA\Sharing\ResponseDefinitions;

/**
 * @psalm-import-type SharingCompatible from ResponseDefinitions
 * @psalm-import-type SharingFeature from ResponseDefinitions
 */
abstract class AShareFeature {
	/**
	 * @return list<SharingCompatible>
	 */
	abstract public function getCompatibles(): array;

	/**
	 * @param array<string, list<string>> $properties
	 */
	abstract public function validateProperties(array $properties): bool;

	/**
	 * @return SharingFeature
	 */
	final public function toArray(): array {
		return [
			'compatibles' => $this->getCompatibles(),
		];
	}
}
