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
use OCP\AppFramework\Http\InvalidStringParameterException;
use OCP\AppFramework\Http\ParameterOutOfRangeException;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

/**
 * Turns a controller parameter validation failure detected by the Dispatcher
 * into a 400 Bad Request response.
 */
class InvalidParameterMiddleware extends Middleware {
	/**
	 * @throws \Exception
	 */
	#[\Override]
	public function afterException(Controller $controller, string $methodName, \Exception $exception): Response {
		if ($exception instanceof ParameterOutOfRangeException
			|| $exception instanceof InvalidStringParameterException
			|| $exception instanceof InvalidEnumParameterException) {
			return new DataResponse(['message' => $exception->getMessage()], Http::STATUS_BAD_REQUEST);
		}

		throw $exception;
	}
}
