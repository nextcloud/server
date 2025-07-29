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
	 * @param string $resourceId cloud federation resourceId
	 * @param string|null $shareId cloud federation shareId
	 */
	public function __construct($resourceId, $shareId) {
		$l = \OC::$server->getL10N('federation');
		$message = 'Cloud Federation Provider with resourceId: "' . $resourceId . '" and shareId: "' . $shareId . '" does not exist.';
		$hint = $l->t('Cloud Federation Provider with resourceId: "%s" and shareId: "%s" does not exist.', [$resourceId, $shareId]);
		parent::__construct($message, $hint);
	}
}
