<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Comments;

use OCP\AppFramework\Attribute\Catchable;

/**
 * Exception for not found entity
 *
 * @since 9.0.0
 */
#[Catchable('9.0.0')]
class NotFoundException extends \Exception {
}
