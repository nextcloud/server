<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Exceptions;

use OCP\HintException;

/**
 * Class ProviderDoesNotExistsException
 *
 *
 * @since 14.0.0
 */
class ProviderDoesNotExistsException extends HintException {
	/**
	 * ProviderDoesNotExistsException constructor.
	 *
	 * @since 14.0.0
	 *
	 * @param string $providerId cloud federation provider ID
	 */
	public function __construct($providerId) {
		$l = \OCP\Util::getL10N('federation');
		$message = 'Cloud Federation Provider with ID: "' . $providerId . '" does not exist.';
		$hint = $l->t('Cloud Federation Provider with ID: "%s" does not exist.', [$providerId]);
		parent::__construct($message, $hint);
	}
}
