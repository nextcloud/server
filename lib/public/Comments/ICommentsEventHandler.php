<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Comments;

/**
 * Interface ICommentsEventHandler
 *
 * @since 11.0.0
 * @deprecated 30.0.0 Register a listener for the CommentsEvent through the IEventDispatcher
 */
interface ICommentsEventHandler {
	/**
	 * @param CommentsEvent $event
	 * @since 11.0.0
	 * @deprecated 30.0.0 Register a listener for the CommentsEvent through the IEventDispatcher
	 */
	public function handle(CommentsEvent $event);
}
