<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\AbstractStringCheck;
use OCP\IL10N;
use PHPUnit\Framework\MockObject\MockObject;

class AbstractStringCheckTest extends \Test\TestCase {
	protected function getCheckMock(): AbstractStringCheck|MockObject {
		$l = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return sprintf($string, $args);
			});

		$check = $this->getMockBuilder(AbstractStringCheck::class)
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

	public static function dataExecuteStringCheck(): array {
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataExecuteStringCheck')]
	public function testExecuteStringCheck(string $operation, string $checkValue, string $actualValue, bool $expected): void {
		$check = $this->getCheckMock();

		/** @var AbstractStringCheck $check */
		$this->assertEquals($expected, $this->invokePrivate($check, 'executeStringCheck', [$operation, $checkValue, $actualValue]));
	}

	public static function dataValidateCheck(): array {
		return [
			['is', '/Invalid(Regex/'],
			['!is', '/Invalid(Regex/'],
			['matches', '/Valid(Regex)/'],
			['!matches', '/Valid(Regex)/'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataValidateCheck')]
	public function testValidateCheck(string $operator, string $value): void {
		$check = $this->getCheckMock();

		/** @var AbstractStringCheck $check */
		$check->validateCheck($operator, $value);

		$this->addToAssertionCount(1);
	}

	public static function dataValidateCheckInvalid(): array {
		return [
			['!!is', '', 1, 'The given operator is invalid'],
			['less', '', 1, 'The given operator is invalid'],
			['matches', '/Invalid(Regex/', 2, 'The given regular expression is invalid'],
			['!matches', '/Invalid(Regex/', 2, 'The given regular expression is invalid'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataValidateCheckInvalid')]
	public function testValidateCheckInvalid(string $operator, string $value, int $exceptionCode, string $exceptionMessage): void {
		$check = $this->getCheckMock();

		try {
			/** @var AbstractStringCheck $check */
			$check->validateCheck($operator, $value);
		} catch (\UnexpectedValueException $e) {
			$this->assertEquals($exceptionCode, $e->getCode());
			$this->assertEquals($exceptionMessage, $e->getMessage());
		}
	}

	public static function dataMatch(): array {
		return [
			['/valid/', 'valid', [], true],
			['/valid/', 'valid', [md5('/valid/') => [md5('valid') => false]], false], // Cache hit
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataMatch')]
	public function testMatch(string $pattern, string $subject, array $matches, bool $expected): void {
		$check = $this->getCheckMock();

		$this->invokePrivate($check, 'matches', [$matches]);

		$this->assertEquals($expected, $this->invokePrivate($check, 'match', [$pattern, $subject]));
	}
}
