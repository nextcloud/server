<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 28.0.0
 */
class NodeRemovedFromFavorite extends Event {
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
