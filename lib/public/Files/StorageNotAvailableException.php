<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

use OCP\HintException;

/**
 * Storage is temporarily not available
 * @since 6.0.0
 * @since 8.2.1 based on HintException
 */
class StorageNotAvailableException extends HintException {
	/**
	 * @since 8.2.0
	 */
	public const STATUS_SUCCESS = 0;

	/**
	 * @since 8.2.0
	 */
	public const STATUS_ERROR = 1;

	/**
	 * @since 8.2.0
	 */
	public const STATUS_INDETERMINATE = 2;

	/**
	 * @since 8.2.0
	 */
	public const STATUS_INCOMPLETE_CONF = 3;

	/**
	 * @since 8.2.0
	 */
	public const STATUS_UNAUTHORIZED = 4;

	/**
	 * @since 8.2.0
	 */
	public const STATUS_TIMEOUT = 5;

	/**
	 * @since 8.2.0
	 */
	public const STATUS_NETWORK_ERROR = 6;

	/**
	 * StorageNotAvailableException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param \Exception|null $previous
	 * @since 6.0.0
	 */
	public function __construct($message = '', $code = self::STATUS_ERROR, ?\Exception $previous = null) {
		$l = \OCP\Util::getL10N('core');
		parent::__construct($message, $l->t('Storage is temporarily not available'), $code, $previous);
	}

	/**
	 * Get the name for a status code
	 *
	 * @param int $code
	 * @return string
	 * @since 9.0.0
	 */
	public static function getStateCodeName($code) {
		switch ($code) {
			case self::STATUS_SUCCESS:
				return 'ok';
			case self::STATUS_ERROR:
				return 'error';
			case self::STATUS_INDETERMINATE:
				return 'indeterminate';
			case self::STATUS_UNAUTHORIZED:
				return 'unauthorized';
			case self::STATUS_TIMEOUT:
				return 'timeout';
			case self::STATUS_NETWORK_ERROR:
				return 'network error';
			default:
				return 'unknown';
		}
	}
}
