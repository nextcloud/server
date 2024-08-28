<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\OCS;

use Exception;
use OCP\AppFramework\Http;

/**
 * Class OCSPreconditionFailedException
 *
 * @since 28.0.0
 */
class OCSPreconditionFailedException extends OCSException {
	/**
	 * OCSPreconditionFailedException constructor.
	 *
	 * @param string $message
	 * @param Exception|null $previous
	 * @since 9.1.0
	 */
	public function __construct($message = '', ?Exception $previous = null) {
		parent::__construct($message, Http::STATUS_PRECONDITION_FAILED, $previous);
	}
}
