<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Http;

use OCP\AppFramework\Http\InvalidPayloadException;
use OCP\AppFramework\Http\ValidationFailedException;
use OCP\Serializer\Format;
use OCP\Serializer\ISerializer;
use OCP\Validator\IValidator;

/**
 * Builds a {@see \OCP\AppFramework\Http\Attribute\RequestPayload} controller argument from the
 * raw JSON request body
 */
class RequestPayloadResolver {
	public function __construct(
		private readonly ISerializer $serializer,
		private readonly IValidator $validator,
	) {
	}

	/**
	 * @template T
	 * @param class-string<T> $type
	 * @param string|string[]|null $validationGroups
	 * @return T
	 * @throws InvalidPayloadException if `$rawContent` is not valid JSON, or does not satisfy `$type`'s constructor
	 * @throws ValidationFailedException if the built object does not satisfy its own validation constraints
	 */
	public function resolve(string $parameterName, string $type, ?string $rawContent, string|array|null $validationGroups): object {
		try {
			$payload = $this->serializer->deserialize($rawContent ?? '', $type, Format::JSON);
		} catch (\Throwable $e) {
			throw new InvalidPayloadException($parameterName, $e->getMessage());
		}

		$violations = $this->validator->validate($payload, $validationGroups);
		if ($violations !== []) {
			throw new ValidationFailedException($parameterName, $violations);
		}

		return $payload;
	}
}
