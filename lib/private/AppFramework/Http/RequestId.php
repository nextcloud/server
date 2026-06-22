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

	#[\Override]
	public function getId(): string {
		if (empty($this->requestId)) {
			$validChars = ISecureRandom::CHAR_ALPHANUMERIC;
			$this->requestId = $this->secureRandom->generate(20, $validChars);
		}

		return $this->requestId;
	}
}
