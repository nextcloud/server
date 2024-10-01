<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Search;

use Exception;

final class UnsupportedFilter extends Exception {
	public function __construct(string $filerName, $providerId) {
		parent::__construct('Provider ' . $providerId . ' doesn’t support filter ' . $filerName . '.');
	}
}
