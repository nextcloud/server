<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\RichObjectStrings;

use OC\RichObjectStrings\Validator;
use OCP\RichObjectStrings\Definitions;
use Test\TestCase;

class ValidatorTest extends TestCase {
	public function test() {
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
	}
}
