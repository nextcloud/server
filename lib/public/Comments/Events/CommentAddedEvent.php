<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments\Events;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Comments\CommentsEvent;
use OCP\Comments\IComment;

/**
 * Class CommentAddedEvent
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
final class CommentAddedEvent extends CommentsEvent {
	/**
	 * CommentAddedEvent constructor.
	 */
	public function __construct(IComment $comment) {
		/** @psalm-suppress DeprecatedConstant */
		parent::__construct(CommentsEvent::EVENT_ADD, $comment);
	}
}
