<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

/**
 * Exception thrown when a comment message exceeds the allowed character limit
 * @since 9.0.0
 */
class MessageTooLongException extends \OverflowException {
}
