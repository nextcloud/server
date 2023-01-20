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
