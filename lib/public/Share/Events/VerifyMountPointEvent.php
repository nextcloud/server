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
	/**
	 * @since 19.0.0
	 */
	public function __construct(
		private IShare $share,
		private readonly IUser $recipient,
		private string $parent
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
	 * @depreacted 33.0.0 use getRecipient instead to get the user folder
	 */
	public function getView(): View {
		return new View('/' . $this->recipient->getUID() . '/files');
	}

	/**
	 * @since 33.0.0
	 */
	public function getRecipient(): IUser {
		return $this->recipient;
	}

	/**
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
}
