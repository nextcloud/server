<?php

declare(strict_types=1);

/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		IConversationOptions $options = null): IConversation;

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
