<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Http;

use OCP\IRequestId;
use OCP\Security\ISecureRandom;

class RequestId implements IRequestId {
	public function __construct(
		protected string $requestId,
		protected ISecureRandom $secureRandom,
	) {
	}

	/**
	 * Returns an ID for the request, value is not guaranteed to be unique and is mostly meant for logging
	 * If `mod_unique_id` is installed this value will be taken.
	 * @return string
	 */
	public function getId(): string {
		if (empty($this->requestId)) {
			$validChars = ISecureRandom::CHAR_ALPHANUMERIC;
			$this->requestId = $this->secureRandom->generate(20, $validChars);
		}

		return $this->requestId;
	}
}
