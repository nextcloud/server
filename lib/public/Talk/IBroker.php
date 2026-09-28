<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Talk;

use OCP\IUser;
use OCP\Talk\Exceptions\NoBackendException;

/**
 * Abstraction over the optional Talk backend
 *
 * http://software-pattern.org/Broker
 *
 * @since 24.0.0
 */
interface IBroker {
	/**
	 * Check if the Talk backend is available
	 *
	 * @return bool
	 * @since 24.0.0
	 */
	public function hasBackend(): bool;

	/**
	 * Create a new instance of the objects object for specifics of a new conversation
	 *
	 * @return IConversationOptions
	 * @throws NoBackendException when Talk is not available
	 * @since 24.0.0
	 */
	public function newConversationOptions(): IConversationOptions;

	/**
	 * Create a new conversation
	 *
	 * The conversation is private by default. Use the options parameter to make
	 * it public.
	 *
	 * @param string $name
	 * @param IUser[] $moderators
	 * @param IConversationOptions|null $options optional configuration for the conversation
	 *
	 * @return IConversation
	 * @throws NoBackendException when Talk is not available
	 * @since 24.0.0
	 */
	public function createConversation(string $name,
		array $moderators,
		?IConversationOptions $options = null): IConversation;

	/**
	 * Delete a conversation by id
	 *
	 * @param string $id conversation id
	 *
	 * @return void
	 * @throws NoBackendException when Talk is not available
	 * @since 26.0.0
	 */
	public function deleteConversation(string $id): void;
}
