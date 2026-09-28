<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Exceptions;

use OCP\HintException;

/**
 * Class AuthenticationFailedException
 *
 *
 * @since 14.0.0
 */
class AuthenticationFailedException extends HintException {
	/**
	 * BadRequestException constructor.
	 *
	 * @since 14.0.0
	 *
	 */
	public function __construct() {
		$l = \OCP\Util::getL10N('federation');
		$message = 'Authentication failed, wrong token or provider ID given';
		$hint = $l->t('Authentication failed, wrong token or provider ID given');
		parent::__construct($message, $hint);
	}
}
