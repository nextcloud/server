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
 * Class ForbiddenException
 *
 * @since 9.0.0
 */
class ForbiddenException extends \Exception {
	/** @var bool */
	private $retry;

	/**
	 * @param string $message
	 * @param bool $retry
	 * @param \Exception|null $previous previous exception for cascading
	 * @since 9.0.0
	 */
	public function __construct($message, $retry, ?\Exception $previous = null) {
		parent::__construct($message, 0, $previous);
		$this->retry = $retry;
	}

	/**
	 * @return bool
	 * @since 9.0.0
	 */
	public function getRetry() {
		return (bool)$this->retry;
	}
}
