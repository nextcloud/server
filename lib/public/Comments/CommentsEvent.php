<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

use OCP\EventDispatcher\Event;

/**
 * Class CommentsEvent
 *
 * @since 9.0.0
 */
class CommentsEvent extends Event {
	/**
	 * @since 11.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_ADD = 'OCP\Comments\ICommentsManager::addComment';

	/**
	 * @since 11.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_PRE_UPDATE = 'OCP\Comments\ICommentsManager::preUpdateComment';

	/**
	 * @since 11.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_UPDATE = 'OCP\Comments\ICommentsManager::updateComment';

	/**
	 * @since 11.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_DELETE = 'OCP\Comments\ICommentsManager::deleteComment';

	/** @var string */
	protected $event;
	/** @var IComment */
	protected $comment;

	/**
	 * DispatcherEvent constructor.
	 *
	 * @param string $event
	 * @param IComment $comment
	 * @since 9.0.0
	 */
	public function __construct($event, IComment $comment) {
		$this->event = $event;
		$this->comment = $comment;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @return IComment
	 * @since 9.0.0
	 */
	public function getComment() {
		return $this->comment;
	}
}
