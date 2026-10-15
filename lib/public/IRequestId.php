<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * @since 24.0.0
 */
interface IRequestId {

	/**
	 * Returns a request identifier intended primarily for logging and tracing.
	 *
	 * The value is not guaranteed to be globally unique. If `mod_unique_id` is
	 * installed, that value may be used by the implementation.
	 *
	 * @return string
	 * @since 24.0.0
	 */
	public function getId(): string;
}
