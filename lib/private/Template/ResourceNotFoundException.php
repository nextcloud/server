<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Template;

class ResourceNotFoundException extends \LogicException {
	public function __construct(
		protected string $resource,
		protected string $webPath,
	) {
		parent::__construct('Resource not found');
	}

	public function getResourcePath(): string {
		return $this->webPath . '/' . $this->resource;
	}
}
