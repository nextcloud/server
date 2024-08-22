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
use OCP\Files\InvalidDirectoryException;
use OCP\Files\InvalidPathException;
use OCP\Files\ReservedWordException;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class FilenameValidatorTest extends TestCase {

	protected IFactory&MockObject $l10n;
	protected IConfig&MockObject $config;
	protected IDBConnection&MockObject $database;
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
		$this->database = $this->createMock(IDBConnection::class);
		$this->database->method('supports4ByteText')->willReturn(true);
	}

	/**
	 * @dataProvider dataValidateFilename
	 */
	public function testValidateFilename(
		string $filename,
		array $forbiddenNames,
		array $forbiddenBasenames,
		array $forbiddenExtensions,
		array $forbiddenCharacters,
		?string $exception,
	): void {
		/** @var FilenameValidator&MockObject */
		$validator = $this->getMockBuilder(FilenameValidator::class)
			->onlyMethods([
				'getForbiddenBasenames',
				'getForbiddenCharacters',
				'getForbiddenExtensions',
				'getForbiddenFilenames',
			])
			->setConstructorArgs([$this->l10n, $this->database, $this->config, $this->logger])
			->getMock();

		$validator->method('getForbiddenBasenames')
			->willReturn($forbiddenBasenames);
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
		array $forbiddenBasenames,
		array $forbiddenExtensions,
		array $forbiddenCharacters,
		?string $exception,
	): void {
		/** @var FilenameValidator&MockObject */
		$validator = $this->getMockBuilder(FilenameValidator::class)
			->onlyMethods([
				'getForbiddenBasenames',
				'getForbiddenExtensions',
				'getForbiddenFilenames',
				'getForbiddenCharacters',
			])
			->setConstructorArgs([$this->l10n, $this->database, $this->config, $this->logger])
			->getMock();

		$validator->method('getForbiddenBasenames')
			->willReturn($forbiddenBasenames);
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
				'a: b.txt', ['.htaccess'], [], [], [], null
			],
			'forbidden name in the middle is ok' => [
				'a.htaccess.txt', ['.htaccess'], [], [], [], null
			],
			'valid name with some more parameters' => [
				'a: b.txt', ['.htaccess'], [], ['exe'], ['~'], null
			],
			'valid name checks only the full name' => [
				'.htaccess.sample', ['.htaccess'], [], [], [], null
			],
			'forbidden name' => [
				'.htaccess', ['.htaccess'], [], [], [], ReservedWordException::class
			],
			'forbidden name - name is case insensitive' => [
				'COM1', ['.htaccess', 'com1'], [], [], [], ReservedWordException::class
			],
			'forbidden basename' => [
				// needed for Windows namespaces
				'com1.suffix', ['.htaccess'], ['com1'], [], [], ReservedWordException::class
			],
			'forbidden basename for hidden files' => [
				// needed for Windows namespaces
				'.thumbs.db', ['.htaccess'], ['.thumbs'], [], [], ReservedWordException::class
			],
			'invalid character' => [
				'a: b.txt', ['.htaccess'], [], [], [':'], InvalidCharacterInPathException::class
			],
			'invalid path' => [
				'../../foo.bar', ['.htaccess'], [], [], ['/', '\\'], InvalidCharacterInPathException::class,
			],
			'invalid extension' => [
				'a: b.txt', ['.htaccess'], [], ['.txt'], [], InvalidPathException::class
			],
			'empty filename' => [
				'', [], [], [], [], EmptyFileNameException::class
			],
			'reserved unix name "."' => [
				'.', [], [], [], [], InvalidDirectoryException::class
			],
			'reserved unix name ".."' => [
				'..', [], [], [], [], InvalidDirectoryException::class
			],
			'weird but valid tripple dot name' => [
				'...', [], [], [], [], null // is valid
			],
			'too long filename "."' => [
				str_repeat('a', 251), [], [], [], [], FileNameTooLongException::class
			],
			// make sure to not split the list entries as they migh contain Unicode sequences
			// in this example the "face in clouds" emoji contains the clouds emoji so only having clouds is ok
			['🌫️.txt', ['.htaccess'], [], [], ['😶‍🌫️'], null],
			// This is the reverse: clouds are forbidden -> so is also the face in the clouds emoji
			['😶‍🌫️.txt', ['.htaccess'], [], [], ['🌫️'], InvalidCharacterInPathException::class],
		];
	}

	/**
	 * @dataProvider data4ByteUnicode
	 */
	public function testDatabaseDoesNotSupport4ByteText($filename): void {
		$database = $this->createMock(IDBConnection::class);
		$database->expects($this->once())
			->method('supports4ByteText')
			->willReturn(false);
		$this->expectException(InvalidCharacterInPathException::class);
		$validator = new FilenameValidator($this->l10n, $database, $this->config, $this->logger);
		$validator->validateFilename($filename);
	}

	public function data4ByteUnicode(): array {
		return [
			['plane 1 𐪅'],
			['emoji 😶‍🌫️'],

		];
	}

	/**
	 * @dataProvider dataInvalidAsciiCharacters
	 */
	public function testInvalidAsciiCharactersAreAlwaysForbidden(string $filename): void {
		$this->expectException(InvalidPathException::class);
		$validator = new FilenameValidator($this->l10n, $this->database, $this->config, $this->logger);
		$validator->validateFilename($filename);
	}

	public function dataInvalidAsciiCharacters(): array {
		return [
			[\chr(0)],
			[\chr(1)],
			[\chr(2)],
			[\chr(3)],
			[\chr(4)],
			[\chr(5)],
			[\chr(6)],
			[\chr(7)],
			[\chr(8)],
			[\chr(9)],
			[\chr(10)],
			[\chr(11)],
			[\chr(12)],
			[\chr(13)],
			[\chr(14)],
			[\chr(15)],
			[\chr(16)],
			[\chr(17)],
			[\chr(18)],
			[\chr(19)],
			[\chr(20)],
			[\chr(21)],
			[\chr(22)],
			[\chr(23)],
			[\chr(24)],
			[\chr(25)],
			[\chr(26)],
			[\chr(27)],
			[\chr(28)],
			[\chr(29)],
			[\chr(30)],
			[\chr(31)],
		];
	}

	/**
	 * @dataProvider dataIsForbidden
	 */
	public function testIsForbidden(string $filename, array $forbiddenNames, bool $expected): void {
		/** @var FilenameValidator&MockObject */
		$validator = $this->getMockBuilder(FilenameValidator::class)
			->onlyMethods(['getForbiddenFilenames'])
			->setConstructorArgs([$this->l10n, $this->database, $this->config, $this->logger])
			->getMock();

		$validator->method('getForbiddenFilenames')
			->willReturn($forbiddenNames);

		$this->assertEquals($expected, $validator->isForbidden($filename));
	}

	public function dataIsForbidden(): array {
		return [
			'valid name' => [
				'a: b.txt', ['.htaccess'], false
			],
			'valid name with some more parameters' => [
				'a: b.txt', ['.htaccess'], false
			],
			'valid name as only full forbidden should be matched' => [
				'.htaccess.sample', ['.htaccess'], false,
			],
			'forbidden name' => [
				'.htaccess', ['.htaccess'], true
			],
			'forbidden name - name is case insensitive' => [
				'COM1', ['.htaccess', 'com1'], true,
			],
		];
	}
}
