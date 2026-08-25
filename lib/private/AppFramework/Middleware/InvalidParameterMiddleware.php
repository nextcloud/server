<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\AppFramework\Middleware;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\InvalidEnumParameterException;
use OCP\AppFramework\Http\InvalidPayloadException;
use OCP\AppFramework\Http\InvalidStringParameterException;
use OCP\AppFramework\Http\ParameterOutOfRangeException;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\ValidationFailedException;
use OCP\AppFramework\Middleware;
use OCP\Validator\Violation;

/**
 * Turns a controller parameter validation failure detected by the Dispatcher into a 400 Bad
 * Request response, or a 422 Unprocessable Entity response for a failed
 * {@see \OCP\AppFramework\Http\Attribute\RequestPayload} validation.
 */
class InvalidParameterMiddleware extends Middleware {
	/**
	 * @throws \Exception
	 */
	#[\Override]
	public function afterException(Controller $controller, string $methodName, \Exception $exception): Response {
		if ($exception instanceof ParameterOutOfRangeException
			|| $exception instanceof InvalidStringParameterException
			|| $exception instanceof InvalidEnumParameterException
			|| $exception instanceof InvalidPayloadException) {
			return new DataResponse(['message' => $exception->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		if ($exception instanceof ValidationFailedException) {
			return new DataResponse([
				'message' => $exception->getMessage(),
				'violations' => array_map(
					static fn (Violation $violation): array => [
						'propertyPath' => $violation->propertyPath,
						'message' => $violation->message,
					],
					$exception->violations,
				),
			], Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		throw $exception;
	}
}
