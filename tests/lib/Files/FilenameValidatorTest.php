<?php

declare(strict_types=1);

/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files;

use OC\Files\FilenameValidator;
use OCP\Files\EmptyFileNameException;
use OCP\Files\FileNameTooLongException;
use OCP\Files\InvalidCharacterInPathException;
use OCP\Files\InvalidPathException;
use OCP\Files\ReservedWordException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FilenameValidatorTest extends TestCase {

	protected IFactory&MockObject $l10n;
	protected IConfig&MockObject $config;
	protected LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')
			->willReturnCallback(fn ($string, $params) => sprintf($string, ...$params));
		$this->l10n = $this->createMock(IFactory::class);
		$this->l10n
			->method('get')
			->with('core')
			->willReturn($l10n);

		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * @dataProvider dataValidateFilename
	 */
	public function testValidateFilename(
		string $filename,
		array $forbiddenNames,
		array $forbiddenExtensions,
		array $forbiddenCharacters,
		?string $exception,
	): void {
		/** @var FilenameValidator&MockObject */
		$validator = $this->getMockBuilder(FilenameValidator::class)
			->onlyMethods(['getForbiddenExtensions', 'getForbiddenFilenames', 'getForbiddenCharacters'])
			->setConstructorArgs([$this->l10n, $this->config, $this->logger])
			->getMock();

		$validator->method('getForbiddenCharacters')
			->willReturn($forbiddenCharacters);
		$validator->method('getForbiddenExtensions')
			->willReturn($forbiddenExtensions);
		$validator->method('getForbiddenFilenames')
			->willReturn($forbiddenNames);

		if ($exception !== null) {
			$this->expectException($exception);
		} else {
			$this->expectNotToPerformAssertions();
		}
		$validator->validateFilename($filename);
	}

	/**
	 * @dataProvider dataValidateFilename
	 */
	public function testIsFilenameValid(
		string $filename,
		array $forbiddenNames,
		array $forbiddenExtensions,
		array $forbiddenCharacters,
		?string $exception,
	): void {
		/** @var FilenameValidator&MockObject */
		$validator = $this->getMockBuilder(FilenameValidator::class)
			->onlyMethods(['getForbiddenExtensions', 'getForbiddenFilenames', 'getForbiddenCharacters'])
			->setConstructorArgs([$this->l10n, $this->config, $this->logger])
			->getMock();

		$validator->method('getForbiddenCharacters')
			->willReturn($forbiddenCharacters);
		$validator->method('getForbiddenExtensions')
			->willReturn($forbiddenExtensions);
		$validator->method('getForbiddenFilenames')
			->willReturn($forbiddenNames);


		$this->assertEquals($exception === null, $validator->isFilenameValid($filename));
	}

	public function dataValidateFilename(): array {
		return [
			'valid name' => [
				'a: b.txt', ['.htaccess'], [], [], null
			],
			'valid name with some more parameters' => [
				'a: b.txt', ['.htaccess'], ['exe'], ['~'], null
			],
			'forbidden name' => [
				'.htaccess', ['.htaccess'], [], [], ReservedWordException::class
			],
			'forbidden name - name is case insensitive' => [
				'COM1', ['.htaccess', 'com1'], [], [], ReservedWordException::class
			],
			'forbidden name - name checks the filename' => [
				// needed for Windows namespaces
				'com1.suffix', ['.htaccess', 'com1'], [], [], ReservedWordException::class
			],
			'invalid character' => [
				'a: b.txt', ['.htaccess'], [], [':'], InvalidCharacterInPathException::class
			],
			'invalid path' => [
				'../../foo.bar', ['.htaccess'], [], ['/', '\\'], InvalidCharacterInPathException::class,
			],
			'invalid extension' => [
				'a: b.txt', ['.htaccess'], ['.txt'], [], InvalidPathException::class
			],
			'empty filename' => [
				'', [], [], [], EmptyFileNameException::class
			],
			'reserved unix name "."' => [
				'.', [], [], [], InvalidPathException::class
			],
			'reserved unix name ".."' => [
				'..', [], [], [], ReservedWordException::class
			],
			'too long filename "."' => [
				str_repeat('a', 251), [], [], [], FileNameTooLongException::class
			],
			// make sure to not split the list entries as they migh contain Unicode sequences
			// in this example the "face in clouds" emoji contains the clouds emoji so only having clouds is ok
			['🌫️.txt', ['.htaccess'], [], ['😶‍🌫️'], null],
			// This is the reverse: clouds are forbidden -> so is also the face in the clouds emoji
			['😶‍🌫️.txt', ['.htaccess'], [], ['🌫️'], InvalidCharacterInPathException::class],
		];
	}

	/**
	 * @dataProvider dataIsForbidden
	 */
	public function testIsForbidden(string $filename, array $forbiddenNames, bool $expected): void {
		/** @var FilenameValidator&MockObject */
		$validator = $this->getMockBuilder(FilenameValidator::class)
			->onlyMethods(['getForbiddenFilenames'])
			->setConstructorArgs([$this->l10n, $this->config, $this->logger])
			->getMock();

		$validator->method('getForbiddenFilenames')
			->willReturn($forbiddenNames);


		$this->assertEquals($expected, $validator->isFilenameValid($filename));
	}

	public function dataIsForbidden(): array {
		return [
			'valid name' => [
				'a: b.txt', ['.htaccess'], true
			],
			'valid name with some more parameters' => [
				'a: b.txt', ['.htaccess'], true
			],
			'forbidden name' => [
				'.htaccess', ['.htaccess'], false
			],
			'forbidden name - name is case insensitive' => [
				'COM1', ['.htaccess', 'com1'], false
			],
			'forbidden name - name checks the filename' => [
				// needed for Windows namespaces
				'com1.suffix', ['.htaccess', 'com1'], false
			],
		];
	}
}
