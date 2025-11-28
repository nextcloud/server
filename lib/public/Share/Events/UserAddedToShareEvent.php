<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

/**
 * A user has gained access to an existing share, for example by being added
 * to a group that is receiving a share.
 *
 * Note that this event might be emitted as a false positive in cases where a user
 * has access to a share trough multiple paths
 *
 * @since 33.0.0
 */
class UserAddedToShareEvent extends Event {
	/**
	 * @since 33.0.0
	 */
	public function __construct(
		private readonly IShare $share,
		private readonly IUser $user,
	) {
		parent::__construct();
	}

	/**
	 * @since 33.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}

	/**
	 * @since 33.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
