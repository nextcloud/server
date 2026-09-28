<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share\Exceptions;

use OCP\Share\IShare;

/**
 * @since 22.0.0
 */
class AlreadySharedException extends GenericShareException {
	/** @var IShare */
	private $existingShare;

	/**
	 * @since 22.0.0
	 */
	public function __construct(string $message, IShare $existingShare) {
		parent::__construct($message);

		$this->existingShare = $existingShare;
	}

	/**
	 * @since 22.0.0
	 */
	public function getExistingShare(): IShare {
		return $this->existingShare;
	}
}
