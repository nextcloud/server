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
interface IConversationOptions {
	/**
	 * Will the conversation be public?
	 *
	 * @return bool
	 * @since 24.0.0
	 */
	public function isPublic(): bool;

	/**
	 * Make the new conversation public
	 *
	 * @param bool $isPublic
	 *
	 * @return $this
	 * @since 24.0.0
	 */
	public function setPublic(bool $isPublic = true): self;
}
