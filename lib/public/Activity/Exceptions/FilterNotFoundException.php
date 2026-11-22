<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Activity\Exceptions;

/**
 * @since 30.0.0
 */
class FilterNotFoundException extends \InvalidArgumentException {
	/**
	 * @since 30.0.0
	 */
	public function __construct(
		protected string $filter,
	) {
		parent::__construct('Filter ' . $filter . ' not found');
	}

	/**
	 * @since 30.0.0
	 */
	public function getFilterId(): string {
		return $this->filter;
	}
}
