<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

use OCP\AppFramework\Attribute\Consumable;
use OCP\EventDispatcher\Event;

/**
 * Class CommentsEvent
 *
 * @since 9.0.0
 *
 * In the future, once the deprecated methods are removed, this class will be abstract.
 */
#[Consumable(since: '9.0.0')]
class CommentsEvent extends Event {
	/**
	 * @since 11.0.0
	 * @deprecated 33.0.0 Use \OCP\Comments\Events\CommentAddedEvent instead.
	 */
	public const EVENT_ADD = 'OCP\Comments\ICommentsManager::addComment';

	/**
	 * @since 11.0.0
	 * @deprecated 33.0.0 Use \OCP\Comments\Events\BeforeCommentUpdatedEvent instead.
	 */
	public const EVENT_PRE_UPDATE = 'OCP\Comments\ICommentsManager::preUpdateComment';

	/**
	 * @since 11.0.0
	 * @deprecated 33.0.0 Use \OCP\Comments\Events\CommentUpdatedEvent instead.
	 */
	public const EVENT_UPDATE = 'OCP\Comments\ICommentsManager::updateComment';

	/**
	 * @since 11.0.0
	 * @deprecated 33.0.0 Use \OCP\Comments\Events\CommentDeletedEvent instead.
	 */
	public const EVENT_DELETE = 'OCP\Comments\ICommentsManager::deleteComment';

	/**
	 * CommentsEvent constructor.
	 *
	 * @since 9.0.0
	 */
	public function __construct(
		protected readonly string $event,
		protected readonly IComment $comment,
	) {
		parent::__construct();
	}

	/**
	 * @since 9.0.0
	 * @depreacted Since 33.0.0 use instanceof CommentAddedEvent, CommentRemovedEvent, CommentUpdatedEvent or BeforeCommentUpdatedEvent instead.
	 */
	public function getEvent(): string {
		return $this->event;
	}

	/**
	 * @since 9.0.0
	 */
	public function getComment(): IComment {
		return $this->comment;
	}
}
