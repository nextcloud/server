<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\ObjectStore;

/**
 * Thrown by a conditional write when an object already exists at the target urn.
 *
 * @see IObjectStoreConditionalWrite::writeObjectIfNotExists()
 * @since 35.0.0
 */
class ObjectAlreadyExistsException extends \Exception {
	/**
	 * @param string $urn the unified resource name of the object that already exists
	 * @since 35.0.0
	 */
	public function __construct(
		private readonly string $urn,
		string $message = '',
		?\Throwable $previous = null,
	) {
		parent::__construct($message === '' ? "Object already exists at $urn" : $message, 0, $previous);
	}

	/**
	 * @return string the unified resource name of the object that already exists
	 * @since 35.0.0
	 */
	public function getUrn(): string {
		return $this->urn;
	}
}
