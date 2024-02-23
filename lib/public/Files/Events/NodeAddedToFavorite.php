<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 28.0.0
 */
class NodeAddedToFavorite extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		protected IUser $user,
		protected int $fileId,
		protected string $path,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 28.0.0
	 */
	public function getFileId(): int {
		return $this->fileId;
	}

	/**
	 * @since 28.0.0
	 */
	public function getPath(): string {
		return $this->path;
	}
}
