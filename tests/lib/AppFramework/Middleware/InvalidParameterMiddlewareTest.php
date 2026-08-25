<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Middleware;

use OC\AppFramework\Middleware\InvalidParameterMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\InvalidPayloadException;
use OCP\AppFramework\Http\ValidationFailedException;
use OCP\Validator\Violation;
use Test\TestCase;

class InvalidParameterMiddlewareTest extends TestCase {
	private InvalidParameterMiddleware $middleware;
	private Controller $controller;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->middleware = new InvalidParameterMiddleware();
		$this->controller = $this->createMock(Controller::class);
	}

	public function testInvalidPayloadExceptionBecomesBadRequest(): void {
		$response = $this->middleware->afterException(
			$this->controller,
			'create',
			new InvalidPayloadException('person', 'malformed JSON'),
		);

		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
	}

	public function testValidationFailedExceptionBecomesUnprocessableEntityWithViolations(): void {
		$violations = [
			new Violation('email', 'This value is not a valid email address.', 'not-an-email'),
		];

		$response = $this->middleware->afterException(
			$this->controller,
			'create',
			new ValidationFailedException('person', $violations),
		);

		$this->assertInstanceOf(DataResponse::class, $response);
		$this->assertSame(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
		$this->assertSame([
			'propertyPath' => 'email',
			'message' => 'This value is not a valid email address.',
		], $response->getData()['violations'][0]);
	}

	public function testOtherExceptionsAreRethrown(): void {
		$exception = new \Exception('unrelated');

		$this->expectExceptionObject($exception);

		$this->middleware->afterException($this->controller, 'create', $exception);
	}
}
