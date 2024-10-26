<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\AbstractStringCheck;
use OCP\IL10N;

class AbstractStringCheckTest extends \Test\TestCase {
	protected function getCheckMock() {
		$l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});

		$check = $this->getMockBuilder('OCA\WorkflowEngine\Check\AbstractStringCheck')
			->setConstructorArgs([
				$l,
			])
			->onlyMethods([
				'executeCheck',
				'getActualValue',
			])
			->getMock();

		return $check;
	}

	public function dataExecuteStringCheck() {
		return [
			['is', 'same', 'same', true],
			['is', 'different', 'not the same', false],
			['!is', 'same', 'same', false],
			['!is', 'different', 'not the same', true],

			['matches', '/match/', 'match', true],
			['matches', '/different/', 'not the same', false],
			['!matches', '/match/', 'match', false],
			['!matches', '/different/', 'not the same', true],
		];
	}

	/**
	 * @dataProvider dataExecuteStringCheck
	 * @param string $operation
	 * @param string $checkValue
	 * @param string $actualValue
	 * @param bool $expected
	 */
	public function testExecuteStringCheck($operation, $checkValue, $actualValue, $expected): void {
		$check = $this->getCheckMock();

		/** @var AbstractStringCheck $check */
		$this->assertEquals($expected, $this->invokePrivate($check, 'executeStringCheck', [$operation, $checkValue, $actualValue]));
	}

	public function dataValidateCheck() {
		return [
			['is', '/Invalid(Regex/'],
			['!is', '/Invalid(Regex/'],
			['matches', '/Valid(Regex)/'],
			['!matches', '/Valid(Regex)/'],
		];
	}

	/**
	 * @dataProvider dataValidateCheck
	 * @param string $operator
	 * @param string $value
	 */
	public function testValidateCheck($operator, $value): void {
		$check = $this->getCheckMock();

		/** @var AbstractStringCheck $check */
		$check->validateCheck($operator, $value);

		$this->addToAssertionCount(1);
	}

	public function dataValidateCheckInvalid() {
		return [
			['!!is', '', 1, 'The given operator is invalid'],
			['less', '', 1, 'The given operator is invalid'],
			['matches', '/Invalid(Regex/', 2, 'The given regular expression is invalid'],
			['!matches', '/Invalid(Regex/', 2, 'The given regular expression is invalid'],
		];
	}

	/**
	 * @dataProvider dataValidateCheckInvalid
	 * @param $operator
	 * @param $value
	 * @param $exceptionCode
	 * @param $exceptionMessage
	 */
	public function testValidateCheckInvalid($operator, $value, $exceptionCode, $exceptionMessage): void {
		$check = $this->getCheckMock();

		try {
			/** @var AbstractStringCheck $check */
			$check->validateCheck($operator, $value);
		} catch (\UnexpectedValueException $e) {
			$this->assertEquals($exceptionCode, $e->getCode());
			$this->assertEquals($exceptionMessage, $e->getMessage());
		}
	}

	public function dataMatch() {
		return [
			['/valid/', 'valid', [], true],
			['/valid/', 'valid', [md5('/valid/') => [md5('valid') => false]], false], // Cache hit
		];
	}

	/**
	 * @dataProvider dataMatch
	 * @param string $pattern
	 * @param string $subject
	 * @param array[] $matches
	 * @param bool $expected
	 */
	public function testMatch($pattern, $subject, $matches, $expected): void {
		$check = $this->getCheckMock();

		$this->invokePrivate($check, 'matches', [$matches]);

		$this->assertEquals($expected, $this->invokePrivate($check, 'match', [$pattern, $subject]));
	}
}
