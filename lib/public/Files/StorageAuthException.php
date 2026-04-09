<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files;

/**
 * Storage authentication exception
 * @since 9.0.0
 */
class StorageAuthException extends StorageNotAvailableException {
	/**
	 * StorageAuthException constructor.
	 *
	 * @param string $message
	 * @param \Exception|null $previous
	 * @since 9.0.0
	 */
	public function __construct($message = '', ?\Exception $previous = null) {
		$l = \OCP\Util::getL10N('core');
		parent::__construct($l->t('Storage unauthorized. %s', [$message]), self::STATUS_UNAUTHORIZED, $previous);
	}
}
