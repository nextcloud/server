<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

/**
 * @since 32.0.0
 *
 * 
 */
interface IEnforcedQuota {
	/**
	 * @since  32.0.0
	 *
	 * The url to redirect to for logout
	 *
	 * @return string
	 */
	public function getEnforcedQuota(): int;
}
