<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\AppFramework\Middleware;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\RequestTimeout;
use OCP\AppFramework\Middleware;
use ReflectionMethod;

class RequestTimeMiddleware extends Middleware {
	public function __construct(
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		// Default timeout
		$timeout = 30;
		$reflectionMethod = new ReflectionMethod($controller, $methodName);
		$attributes = $reflectionMethod->getAttributes(RequestTimeout::class);
		foreach ($attributes as $attribute) {
			/** @var RequestTimeout $timeout */
			$timeoutAttribute = $attribute->newInstance();
			$timeout = $timeoutAttribute->getTimeout();
		}

		@ini_set('max_execution_time', strval($timeout));
	}
}
