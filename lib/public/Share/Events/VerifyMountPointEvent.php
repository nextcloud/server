<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share\Events;

use OC\Files\View;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\Share\IShare;

/**
 * @since 19.0.0
 */
class VerifyMountPointEvent extends Event {
	private bool $createParent = false;

	/**
	 * @since 19.0.0
	 */
	public function __construct(
		private readonly IShare $share,
		private readonly View $view,
		private string $parent,
		private readonly IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @since 19.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}

	/**
	 * @since 19.0.0
	 * @depecated 34.0.0 Get the user folder for `$this->getUser()` instead
	 */
	public function getView(): View {
		return $this->view;
	}

	/**
	 * The parent folder where the share is placed, as relative path to the users home directory.
	 *
	 * @since 19.0.0
	 */
	public function getParent(): string {
		return $this->parent;
	}

	/**
	 * @since 19.0.0
	 */
	public function setParent(string $parent): void {
		$this->parent = $parent;
	}

	/**
	 * @since 34.0.0
	 */
	public function setCreateParent(bool $create): void {
		$this->createParent = $create;
	}

	/**
	 * Whether the parent folder should be created if missing.
	 *
	 * If set for `false` (the default), and the parent folder doesn't exist already,
	 * the share will be moved to the default share folder instead.
	 *
	 * @since 34.0.0
	 */
	public function createParent(): bool {
		return $this->createParent;
	}

	/**
	 * @since 34.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
