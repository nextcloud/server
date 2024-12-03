<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\RichObjectStrings;

use OC\RichObjectStrings\Validator;
use OCP\RichObjectStrings\Definitions;
use OCP\RichObjectStrings\InvalidObjectExeption;
use Test\TestCase;

class ValidatorTest extends TestCase {
	public function testValidate(): void {
		$v = new Validator(new Definitions());
		$v->validate('test', []);
		$v->validate('test {string1} test {foo} test {bar}.', [
			'string1' => [
				'type' => 'user',
				'id' => 'johndoe',
				'name' => 'John Doe',
			],
			'foo' => [
				'type' => 'user-group',
				'id' => 'sample',
				'name' => 'Sample Group',
			],
			'bar' => [
				'type' => 'file',
				'id' => '42',
				'name' => 'test.txt',
				'path' => 'path/to/test.txt',
			],
		]);
		$this->addToAssertionCount(2);

		$this->expectException(InvalidObjectExeption::class);

		$this->expectExceptionMessage('Object is invalid, value 123 is not a string');
		$v->validate('test {string1} test.', [
			'string1' => [
				'type' => 'user',
				'id' => 'johndoe',
				'name' => 'John Doe',
				'key' => 123,
			],
		]);

		$this->expectExceptionMessage('Object is invalid, key 456 is not a string');
		$v->validate('test {string1} test.', [
			'string1' => [
				'type' => 'user',
				'id' => 'johndoe',
				'name' => 'John Doe',
				456 => 'value',
			],
		]);
	}

	public static function dataValidateParameterKeys(): array {
		return [
			'not a string' => ['key' => 0, 'throws' => 'Parameter key is invalid'],
			'@ is not allowed' => ['key' => 'user@0', 'throws' => 'Parameter key is invalid'],
			'? is not allowed' => ['key' => 'user?0', 'throws' => 'Parameter key is invalid'],
			'slash is not allowed' => ['key' => 'user/0', 'throws' => 'Parameter key is invalid'],
			'backslash is not allowed' => ['key' => 'user\\0', 'throws' => 'Parameter key is invalid'],
			'hash is not allowed' => ['key' => 'user#0', 'throws' => 'Parameter key is invalid'],
			'space is not allowed' => ['key' => 'user 0', 'throws' => 'Parameter key is invalid'],
			'has to start with letter, but is number' => ['key' => '0abc', 'throws' => 'Parameter key is invalid'],
			'has to start with letter, but is dot' => ['key' => '.abc', 'throws' => 'Parameter key is invalid'],
			'has to start with letter, but is slash' => ['key' => '-abc', 'throws' => 'Parameter key is invalid'],
			'has to start with letter, but is underscore' => ['key' => '_abc', 'throws' => 'Parameter key is invalid'],
			['key' => 'user-0', 'throws' => null],
			['key' => 'user_0', 'throws' => null],
			['key' => 'user.0', 'throws' => null],
			['key' => 'a._-0', 'throws' => null],
		];
	}

	/**
	 * @dataProvider dataValidateParameterKeys
	 */
	public function testValidateParameterKeys(mixed $key, ?string $throws): void {

		if ($throws !== null) {
			$this->expectExceptionMessage($throws);
		}

		$v = new Validator(new Definitions());
		$v->validate('{' . $key . '}', [
			$key => [
				'type' => 'highlight',
				'id' => 'identifier',
				'name' => 'Display name',
			],
		]);

		if ($throws === null) {
			$this->addToAssertionCount(1);
		}
	}
}
