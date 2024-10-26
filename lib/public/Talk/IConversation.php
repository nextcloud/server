<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Talk;

/**
 * @since 24.0.0
 */
interface IConversation {
	/**
	 * Get the unique token that identifies this conversation
	 *
	 * @return string
	 * @since 26.0.0
	 */
	public function getId(): string;

	/**
	 * Get the absolute URL to this conversation
	 *
	 * @return string
	 * @since 24.0.0
	 */
	public function getAbsoluteUrl(): string;
}
