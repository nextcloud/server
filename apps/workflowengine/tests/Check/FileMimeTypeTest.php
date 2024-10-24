<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\Check;

use OC\Files\Storage\Temporary;
use OCA\WorkflowEngine\Check\FileMimeType;
use OCP\Files\IMimeTypeDetector;
use OCP\IL10N;
use OCP\IRequest;
use Test\TestCase;

class TemporaryNoLocal extends Temporary {
	public function instanceOfStorage(string $class): bool {
		if ($class === '\OC\Files\Storage\Local') {
			return false;
		} else {
			return parent::instanceOfStorage($class);
		}
	}
}

/**
 * @group DB
 */
class FileMimeTypeTest extends TestCase {
	/** @var IL10N */
	private $l10n;
	/** @var IRequest */
	private $request;
	/** @var IMimeTypeDetector */
	private $mimeDetector;

	private $extensions = [
		'.txt' => 'text/plain-path-detected',
	];

	private $content = [
		'text-content' => 'text/plain-content-detected',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->request = $this->createMock(IRequest::class);
		$this->mimeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->mimeDetector->method('detectPath')
			->willReturnCallback(function ($path) {
				foreach ($this->extensions as $extension => $mime) {
					if (str_contains($path, $extension)) {
						return $mime;
					}
				}
				return 'application/octet-stream';
			});
		$this->mimeDetector->method('detectContent')
			->willReturnCallback(function ($path) {
				$body = file_get_contents($path);
				foreach ($this->content as $match => $mime) {
					if (str_contains($body, $match)) {
						return $mime;
					}
				}
				return 'application/octet-stream';
			});
	}

	public function testUseCachedMimetype(): void {
		$storage = new Temporary([]);
		$storage->mkdir('foo');
		$storage->file_put_contents('foo/bar.txt', 'asd');
		$storage->getScanner()->scan('');


		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain'));
	}

	public function testNonCachedNotExists(): void {
		$storage = new Temporary([]);

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain-path-detected'));
	}

	public function testNonCachedLocal(): void {
		$storage = new Temporary([]);
		$storage->mkdir('foo');
		$storage->file_put_contents('foo/bar.txt', 'text-content');

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain-content-detected'));
	}

	public function testNonCachedNotLocal(): void {
		$storage = new TemporaryNoLocal([]);
		$storage->mkdir('foo');
		$storage->file_put_contents('foo/bar.txt', 'text-content');

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain-content-detected'));
	}

	public function testFallback(): void {
		$storage = new Temporary([]);

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'unknown');

		$this->assertTrue($check->executeCheck('is', 'application/octet-stream'));
	}

	public function testFromCacheCached(): void {
		$storage = new Temporary([]);
		$storage->mkdir('foo');
		$storage->file_put_contents('foo/bar.txt', 'text-content');
		$storage->getScanner()->scan('');

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain'));

		$storage->getCache()->clear();

		$this->assertTrue($check->executeCheck('is', 'text/plain'));

		$newCheck = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$newCheck->setFileInfo($storage, 'foo/bar.txt');
		$this->assertTrue($newCheck->executeCheck('is', 'text/plain-content-detected'));
	}

	public function testExistsCached(): void {
		$storage = new TemporaryNoLocal([]);
		$storage->mkdir('foo');
		$storage->file_put_contents('foo/bar.txt', 'text-content');

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain-content-detected'));
		$storage->unlink('foo/bar.txt');
		$this->assertTrue($check->executeCheck('is', 'text/plain-content-detected'));

		$newCheck = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$newCheck->setFileInfo($storage, 'foo/bar.txt');
		$this->assertTrue($newCheck->executeCheck('is', 'text/plain-path-detected'));
	}

	public function testNonExistsNotCached(): void {
		$storage = new TemporaryNoLocal([]);

		$check = new FileMimeType($this->l10n, $this->request, $this->mimeDetector);
		$check->setFileInfo($storage, 'foo/bar.txt');

		$this->assertTrue($check->executeCheck('is', 'text/plain-path-detected'));

		$storage->mkdir('foo');
		$storage->file_put_contents('foo/bar.txt', 'text-content');

		$this->assertTrue($check->executeCheck('is', 'text/plain-content-detected'));
	}
}
