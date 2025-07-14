<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\Check;

use OCA\WorkflowEngine\Check\Directory;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use Test\TestCase;

class DirectoryTest extends TestCase {
	/** @var IL10N */
	private $l10n;

	/** @var IStorage */
	private $storage;

	/** @var Directory */
	private $directory;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->storage = $this->createMock(IStorage::class);
		$this->directory = new Directory($this->l10n);
	}

	/**
	 * @dataProvider dataProviderCheck
	 */
	public function testExecuteStringCheck(string $operator, string $configuredDirectoryPath, string $filePath, bool $expectedResult): void {
		$this->directory->setFileInfo($this->storage, $filePath);

		$result = $this->directory->executeCheck($operator, $configuredDirectoryPath);

		$this->assertEquals($expectedResult, $result);
	}

	public function testSupportedEntities(): void {
		$this->assertSame([File::class], $this->directory->supportedEntities());
	}

	public function testIsAvailableForScope(): void {
		$this->assertTrue($this->directory->isAvailableForScope(1));
	}

	public function dataProviderCheck(): array {
		return [
			['is', 'some/path', 'files/some/path/file.txt', true],
			['is', '/some/path/', 'files/some/path/file.txt', true],

			['!is', 'some/path', 'files/some/path/file.txt', false],
			['!is', 'some/path/', 'files/someother/path/file.txt', true],

			['matches', '/^some\/path\/.+$/i', 'files/SomE/PATH/subfolder/file.txt', true],
			['matches', '/some\/path\/.*\/sub2/', 'files/some/path/subfolder1/sub2/anotherfile.pdf', true],

			['!matches', '/some\/path/', 'files/some/path/file.txt', false],
			['!matches', '/some\/path/', 'files/another/path/file.txt', true],
		];
	}
}
