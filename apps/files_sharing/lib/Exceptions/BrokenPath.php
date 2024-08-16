<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Exceptions;

/**
 * Expected path with a different root
 * Possible Error Codes:
 * 10 - Path not relative to data/ and point to the users file directory
 *
 */
class BrokenPath extends \Exception {
}
