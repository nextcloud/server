<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\AppFramework\Http;

use OCP\IRequestId;
use OCP\Security\ISecureRandom;

class RequestId implements IRequestId {
	protected ISecureRandom $secureRandom;
	protected string $requestId;

	public function __construct(string $uniqueId,
								ISecureRandom $secureRandom) {
		$this->requestId = $uniqueId;
		$this->secureRandom = $secureRandom;
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
