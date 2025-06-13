<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Maintenance\Mimetype;

use OC\Core\Command\Maintenance\Mimetype\UpdateDB;
use OC\Files\Type\Detection;
use OC\Files\Type\Loader;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IMimeTypeLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class UpdateDBTest extends TestCase {
	/** @var IMimeTypeDetector */
	protected $detector;
	/** @var IMimeTypeLoader */
	protected $loader;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleInput;
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $consoleOutput;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp(): void {
		parent::setUp();

		$this->detector = $this->createMock(Detection::class);
		$this->loader = $this->createMock(Loader::class);
		$this->consoleInput = $this->createMock(InputInterface::class);
		$this->consoleOutput = $this->createMock(OutputInterface::class);

		$this->command = new UpdateDB($this->detector, $this->loader);
	}

	public function testNoop(): void {
		$this->consoleInput->method('getOption')
			->with('repair-filecache')
			->willReturn(false);

		$this->detector->expects($this->once())
			->method('getAllMappings')
			->willReturn([
				'ext' => ['testing/existingmimetype']
			]);
		$this->loader->expects($this->once())
			->method('exists')
			->with('testing/existingmimetype')
			->willReturn(true);

		$this->loader->expects($this->never())
			->method('updateFilecache');

		$calls = [
			'Added 0 new mimetypes',
			'Updated 0 filecache rows',
		];
		$this->consoleOutput->expects($this->exactly(2))
			->method('writeln')
			->willReturnCallback(function ($message) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertStringContainsString($expected, $message);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testAddMimetype(): void {
		$this->consoleInput->method('getOption')
			->with('repair-filecache')
			->willReturn(false);

		$this->detector->expects($this->once())
			->method('getAllMappings')
			->willReturn([
				'ext' => ['testing/existingmimetype'],
				'new' => ['testing/newmimetype']
			]);
		$this->loader->expects($this->exactly(2))
			->method('exists')
			->willReturnMap([
				['testing/existingmimetype', true],
				['testing/newmimetype', false],
			]);
		$this->loader->expects($this->exactly(2))
			->method('getId')
			->willReturnMap([
				['testing/existingmimetype', 1],
				['testing/newmimetype', 2],
			]);

		$this->loader->expects($this->once())
			->method('updateFilecache')
			->with('new', 2)
			->willReturn(3);

		$calls = [
			'Added mimetype "testing/newmimetype" to database',
			'Updated 3 filecache rows for mimetype "testing/newmimetype"',
			'Added 1 new mimetypes',
			'Updated 3 filecache rows',
		];
		$this->consoleOutput->expects($this->exactly(4))
			->method('writeln')
			->willReturnCallback(function ($message) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertStringContainsString($expected, $message);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testSkipComments(): void {
		$this->detector->expects($this->once())
			->method('getAllMappings')
			->willReturn([
				'_comment' => 'some comment in the JSON'
			]);
		$this->loader->expects($this->never())
			->method('exists');

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}

	public function testRepairFilecache(): void {
		$this->consoleInput->method('getOption')
			->with('repair-filecache')
			->willReturn(true);

		$this->detector->expects($this->once())
			->method('getAllMappings')
			->willReturn([
				'ext' => ['testing/existingmimetype'],
			]);
		$this->loader->expects($this->exactly(1))
			->method('exists')
			->willReturnMap([
				['testing/existingmimetype', true],
			]);
		$this->loader->expects($this->exactly(1))
			->method('getId')
			->willReturnMap([
				['testing/existingmimetype', 1],
			]);

		$this->loader->expects($this->once())
			->method('updateFilecache')
			->with('ext', 1)
			->willReturn(3);

		$calls = [
			'Updated 3 filecache rows for mimetype "testing/existingmimetype"',
			'Added 0 new mimetypes',
			'Updated 3 filecache rows',
		];
		$this->consoleOutput->expects($this->exactly(3))
			->method('writeln')
			->willReturnCallback(function ($message) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertStringContainsString($expected, $message);
			});

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
