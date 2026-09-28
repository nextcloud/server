<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\IntegrityCheck\Exceptions;

/**
 * Class InvalidSignatureException is thrown in case the signature of the hashes
 * cannot be properly validated. This indicates that either files
 *
 * @package OC\IntegrityCheck\Exceptions
 */
class InvalidSignatureException extends \Exception {
}
