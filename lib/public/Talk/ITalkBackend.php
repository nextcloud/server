<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Talk;

use OCP\IUser;

/**
 * Interface for the Talk app to implement
 *
 * Other apps must not implement nor use this interface in any way. Use the
 * broker instead
 *
 * @see IBroker
 * @since 24.0.0
 */
interface ITalkBackend {
	/**
	 * @param string $name
	 * @param IUser[] $moderators
	 * @param IConversationOptions $options configuration for the conversation
	 *
	 * @return IConversation
	 * @since 24.0.0
	 */
	public function createConversation(string $name,
		array $moderators,
		IConversationOptions $options): IConversation;

	/**
	 * Delete a conversation by id
	 *
	 * @param string $id conversation id
	 *
	 * @return void
	 * @since 26.0.0
	 */
	public function deleteConversation(string $id): void;
}
