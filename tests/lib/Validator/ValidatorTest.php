<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Validator;

use OC\Validator\Validator;
use OCP\Server;
use OCP\Validator\IValidator;
use Test\TestCase;

class ValidatorTest extends TestCase {
	private IValidator $validator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->validator = new Validator();
	}

	private function validDto(): ValidatorTestDto {
		return new ValidatorTestDto(
			name: 'Jane',
			email: 'jane@example.com',
			age: 30,
			role: 'admin',
			username: 'jane_doe',
			tags: ['a'],
		);
	}

	public function testServiceIsRegistered(): void {
		$this->assertInstanceOf(Validator::class, Server::get(IValidator::class));
	}

	public function testValidDataProducesNoViolations(): void {
		$this->assertSame([], $this->validator->validate($this->validDto()));
	}

	public function testNotBlankConstraint(): void {
		// An empty string also violates the Length(min: 2) constraint on the same property,
		// so both are expected to fire.
		$dto = new ValidatorTestDto(name: '', email: 'jane@example.com', age: 30, role: 'admin', username: 'jane_doe', tags: ['a']);

		$violations = $this->validator->validate($dto);

		$this->assertCount(2, $violations);
		$this->assertSame('name', $violations[0]->propertyPath);
		$this->assertSame('name', $violations[1]->propertyPath);
	}

	public function testLengthConstraint(): void {
		$dto = new ValidatorTestDto(name: 'J', email: 'jane@example.com', age: 30, role: 'admin', username: 'jane_doe', tags: ['a']);

		$violations = $this->validator->validate($dto);

		$this->assertCount(1, $violations);
		$this->assertSame('name', $violations[0]->propertyPath);
		$this->assertSame('J', $violations[0]->invalidValue);
	}

	public function testEmailConstraintChecksWithMatchingGroup(): void {
		$dto = new ValidatorTestDto(name: 'Jane', email: 'not-an-email', age: 30, role: 'admin', username: 'jane_doe', tags: ['a']);

		$violations = $this->validator->validate($dto, groups: ['detailed']);

		$this->assertCount(1, $violations);
		$this->assertSame('email', $violations[0]->propertyPath);
		$this->assertSame('not-an-email', $violations[0]->invalidValue);
	}

	public function testEmailConstraintIsSkippedWithoutMatchingGroup(): void {
		$dto = new ValidatorTestDto(name: 'Jane', email: 'not-an-email', age: 30, role: 'admin', username: 'jane_doe', tags: ['a']);

		// The Email constraint only belongs to the "detailed" group, so validating without
		// groups (the implicit "Default" group) does not check it.
		$this->assertSame([], $this->validator->validate($dto));
	}

	public function testRangeConstraint(): void {
		$dto = new ValidatorTestDto(name: 'Jane', email: 'jane@example.com', age: 200, role: 'admin', username: 'jane_doe', tags: ['a']);

		$violations = $this->validator->validate($dto);

		$this->assertCount(1, $violations);
		$this->assertSame('age', $violations[0]->propertyPath);
	}

	public function testChoiceConstraint(): void {
		$dto = new ValidatorTestDto(name: 'Jane', email: 'jane@example.com', age: 30, role: 'superadmin', username: 'jane_doe', tags: ['a']);

		$violations = $this->validator->validate($dto);

		$this->assertCount(1, $violations);
		$this->assertSame('role', $violations[0]->propertyPath);
	}

	public function testRegexConstraint(): void {
		$dto = new ValidatorTestDto(name: 'Jane', email: 'jane@example.com', age: 30, role: 'admin', username: 'Jane Doe!', tags: ['a']);

		$violations = $this->validator->validate($dto);

		$this->assertCount(1, $violations);
		$this->assertSame('username', $violations[0]->propertyPath);
	}

	public function testCountConstraint(): void {
		$dto = new ValidatorTestDto(name: 'Jane', email: 'jane@example.com', age: 30, role: 'admin', username: 'jane_doe', tags: []);

		$violations = $this->validator->validate($dto);

		$this->assertCount(1, $violations);
		$this->assertSame('tags', $violations[0]->propertyPath);
	}

	public function testNotNullConstraint(): void {
		$violations = $this->validator->validate(new ValidatorNotNullTestDto(value: null));

		$this->assertCount(1, $violations);
		$this->assertSame('value', $violations[0]->propertyPath);
	}

	public function testNotNullConstraintPassesForNonNullValue(): void {
		$this->assertSame([], $this->validator->validate(new ValidatorNotNullTestDto(value: 'something')));
	}
}
