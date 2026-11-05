<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events\Node;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\JsonSerializer;
use OCP\Files\Node;
use OCP\IUser;

/**
 * A user opened a node in a client, as reported by that client.
 *
 * Unlike {@see BeforeNodeReadEvent}, this is not dispatched when the content
 * is read (sync, previews, downloads) but only when a client says the user
 * actually opened it.
 *
 * It is meant for statistics only, like recommending the files a user works
 * with. Clients report it when they choose to, so it can be missing, repeated
 * or sent for a node the user never looked at. Never use it for permissions,
 * access control, auditing, or anything else that has to be reliable.
 *
 * @since 36.0.0
 */
#[Listenable(since: '36.0.0')]
class NodeOpenedEvent extends AbstractNodeEvent {
	/**
	 * @since 36.0.0
	 */
	public function __construct(
		Node $node,
		private IUser $user,
	) {
		parent::__construct($node);
	}

	/**
	 * The user who opened the node
	 *
	 * @since 36.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 36.0.0
	 */
	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			...parent::getWebhookSerializable(),
			'user' => JsonSerializer::serializeUser($this->user),
		];
	}
}
