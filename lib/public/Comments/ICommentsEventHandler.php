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
 */
interface ICommentsEventHandler {
	/**
	 * @param CommentsEvent $event
	 * @since 11.0.0
	 */
	public function handle(CommentsEvent $event);
}
