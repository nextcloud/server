<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Exceptions;

use OCP\HintException;

/**
 * Class ActionNotSupportedException
 *
 *
 * @since 14.0.0
 */
class ActionNotSupportedException extends HintException {
	/**
	 * ActionNotSupportedException constructor.
	 *
	 * @since 14.0.0
	 *
	 */
	public function __construct($action) {
		$l = \OCP\Util::getL10N('federation');
		$message = 'Action "' . $action . '" not supported or implemented.';
		$hint = $l->t('Action "%s" not supported or implemented.', [$action]);
		parent::__construct($message, $hint);
	}
}
