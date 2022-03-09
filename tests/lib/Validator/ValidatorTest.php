<?php
/**
 * Copyright (c) 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Validator;

use OCP\Validator\Constraints\Email;
use OCP\Validator\Constraints\Length;
use OCP\Validator\Constraints\NotBlank;
use OCP\Validator\Constraints\Url;
use OCP\Validator\IValidator;
use Test\TestCase;

class ValidatorTest extends TestCase {
	public function testEmailConstraint() {
		/** @var IValidator $validator */
		$validator = \OC::$server->get(IValidator::class);
		$violations = $validator->validate('carl@example.org', [
			new NotBlank(),
			new Email(),
		]);

		$this->assertEmpty($violations);
	}

	public function testNotBlankConstraintInvalid() {
		/** @var IValidator $validator */
		$validator = \OC::$server->get(IValidator::class);
		$violations = $validator->validate('', [
			new NotBlank(),
		]);

		$this->assertEquals(1, count($violations));
		$this->assertEquals('The value is blank', $violations[0]->getMessage());
	}

	/**
	 * @dataProvider urlProviderValid
	 */
	public function testUrl($url, $relativeUrl, $protocols) {
		/** @var IValidator $validator */
		$validator = \OC::$server->get(IValidator::class);
		$violations = $validator->validate($url, [
			new Url($relativeUrl, $protocols),
		]);

		$this->assertEmpty($violations);
	}

	/**
	 * @dataProvider urlProviderInvalid
	 */
	public function testUrlInvalid($url, $relativeUrl, $protocols) {
		/** @var IValidator $validator */
		$validator = \OC::$server->get(IValidator::class);
		$violations = $validator->validate($url, [
			new Url($relativeUrl, $protocols),
		]);

		$this->assertEquals(1, count($violations));
	}
	/**
	 * @dataProvider lengthProviderValid
	 */
	public function testLengthValid($value, $options) {
		/** @var IValidator $validator */
		$validator = \OC::$server->get(IValidator::class);
		$violations = $validator->validate($value, [
			new Length($options)
		]);

		$this->assertEmpty($violations);
	}

	public function lengthProviderValid(): array {
		return [
			['helloworld', ['max' => 300, 'min' => 2]],
			['helloworld', ['exact' => 10]],
		];
	}

	/**
	 * @dataProvider lengthProviderInvalid
	 */
	public function testLengthInvalid($value, $options) {
		/** @var IValidator $validator */
		$validator = \OC::$server->get(IValidator::class);
		$violations = $validator->validate($value, [
			new Length($options)
		]);

		$this->assertEquals(1, count($violations));
	}

	public function lengthProviderInvalid(): array {
		return [
			['helloworld', ['max' => 2]],
			['helloworld', ['min' => 300]],
			['helloworld', ['exact' => 300]],
		];
	}

	public function urlProviderValid(): array {
		return [
			['https://hello.world', false, ['https']],
			['http://hello.world', false, ['http']],
			['http://⌘.ws/', false, ['http']],
			['http://➡.ws/䨹', false, ['http']],
		];
	}

	public function urlProviderInvalid(): array {
		return [
			['example.com/legal', false, ['http', 'https']],  # missing scheme
			['https:///legal', false, ['https']],     # missing host
		];
	}
}
