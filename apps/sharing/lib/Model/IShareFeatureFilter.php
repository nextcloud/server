<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Model;

interface IShareFeatureFilter {
	/**
	 * @param array<string, list<string>> $properties
	 */
	public function isFiltered(array $properties): bool;
}
