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
class SettingNotFoundException extends \InvalidArgumentException {
	/**
	 * @since 30.0.0
	 */
	public function __construct(
		protected string $setting,
	) {
		parent::__construct('Setting ' . $setting . ' not found');
	}

	/**
	 * @since 30.0.0
	 */
	public function getSettingId(): string {
		return $this->setting;
	}
}
