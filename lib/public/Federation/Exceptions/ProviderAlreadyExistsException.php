<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Federation\Exceptions;

use OCP\HintException;

/**
 * Class ProviderAlreadyExistsException
 *
 *
 * @since 14.0.0
 */
class ProviderAlreadyExistsException extends HintException {
	/**
	 * ProviderAlreadyExistsException constructor.
	 *
	 * @since 14.0.0
	 *
	 * @param string $newProviderId cloud federation provider ID of the new provider
	 * @param string $existingProviderName name of cloud federation provider which already use the same ID
	 */
	public function __construct($newProviderId, $existingProviderName) {
		$l = \OCP\Util::getL10N('federation');
		$message = 'ID "' . $newProviderId . '" already used by cloud federation provider "' . $existingProviderName . '"';
		$hint = $l->t('ID "%1$s" already used by cloud federation provider "%2$s"', [$newProviderId, $existingProviderName]);
		parent::__construct($message, $hint);
	}
}
