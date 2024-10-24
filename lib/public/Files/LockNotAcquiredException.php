<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Files;

/**
 * Exception for a file that is locked
 * @since 7.0.0
 */
class LockNotAcquiredException extends \Exception {
	/** @var string $path The path that could not be locked */
	public $path;

	/** @var integer $lockType The type of the lock that was attempted */
	public $lockType;

	/**
	 * @since 7.0.0
	 */
	public function __construct($path, $lockType, $code = 0, ?\Exception $previous = null) {
		$message = \OCP\Util::getL10N('core')->t('Could not obtain lock type %d on "%s".', [$lockType, $path]);
		parent::__construct($message, $code, $previous);
	}

	/**
	 * custom string representation of object
	 *
	 * @since 7.0.0
	 */
	public function __toString(): string {
		return self::class . ": [{$this->code}]: {$this->message}\n";
	}
}
