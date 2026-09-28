<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\SystemTag;

/**
 * Exception when a tag already exists.
 *
 * @since 9.0.0
 */
class TagAlreadyExistsException extends \RuntimeException {
}
