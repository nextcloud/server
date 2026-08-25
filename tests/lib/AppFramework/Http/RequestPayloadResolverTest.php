<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Http;

use OC\AppFramework\Http\RequestPayloadResolver;
use OC\Serializer\Serializer;
use OC\Validator\Validator;
use OCP\AppFramework\Http\InvalidPayloadException;
use OCP\AppFramework\Http\ValidationFailedException;
use OCP\Serializer\Attribute\SerializedName;
use OCP\Validator\Constraints\Email;
use OCP\Validator\Constraints\NotBlank;
use Test\TestCase;

class RequestPayloadResolverTestDto {
	public function __construct(
		#[NotBlank]
		#[SerializedName('full_name')]
		public string $name,
		#[Email]
		public string $email = '',
	) {
	}
}

class RequestPayloadResolverTest extends TestCase {
	private RequestPayloadResolver $resolver;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->resolver = new RequestPayloadResolver(new Serializer(), new Validator());
	}

	public function testResolvesAValidPayload(): void {
		$dto = $this->resolver->resolve(
			'person',
			RequestPayloadResolverTestDto::class,
			'{"full_name": "Jane Doe", "email": "jane@example.com"}',
			null,
		);

		$this->assertInstanceOf(RequestPayloadResolverTestDto::class, $dto);
		$this->assertSame('Jane Doe', $dto->name);
		$this->assertSame('jane@example.com', $dto->email);
	}

	public function testThrowsInvalidPayloadExceptionOnMalformedJson(): void {
		$this->expectException(InvalidPayloadException::class);
		$this->expectExceptionMessage('person');

		$this->resolver->resolve('person', RequestPayloadResolverTestDto::class, '{not json', null);
	}

	public function testThrowsInvalidPayloadExceptionOnEmptyBody(): void {
		$this->expectException(InvalidPayloadException::class);

		$this->resolver->resolve('person', RequestPayloadResolverTestDto::class, null, null);
	}

	public function testThrowsInvalidPayloadExceptionOnMissingRequiredField(): void {
		$this->expectException(InvalidPayloadException::class);

		$this->resolver->resolve('person', RequestPayloadResolverTestDto::class, '{"email": "jane@example.com"}', null);
	}

	public function testThrowsValidationFailedExceptionOnConstraintViolation(): void {
		try {
			$this->resolver->resolve(
				'person',
				RequestPayloadResolverTestDto::class,
				'{"full_name": "Jane Doe", "email": "not-an-email"}',
				null,
			);
			$this->fail('Expected ' . ValidationFailedException::class . ' to be thrown');
		} catch (ValidationFailedException $e) {
			$this->assertSame('person', $e->parameterName);
			$this->assertCount(1, $e->violations);
			$this->assertSame('email', $e->violations[0]->propertyPath);
		}
	}
}
