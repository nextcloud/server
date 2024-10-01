<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Tests\Core\Command\Preview;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Core\Command\Preview\Repair;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class RepairTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;
	/** @var IRootFolder|MockObject */
	private $rootFolder;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var IniGetWrapper|MockObject */
	private $iniGetWrapper;
	/** @var InputInterface|MockObject */
	private $input;
	/** @var OutputInterface|MockObject */
	private $output;
	/** @var string */
	private $outputLines = '';
	/** @var Repair */
	private $repair;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)
			->getMock();
		$this->rootFolder = $this->getMockBuilder(IRootFolder::class)
			->getMock();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->getMock();
		$this->iniGetWrapper = $this->getMockBuilder(IniGetWrapper::class)
			->getMock();
		$this->repair = new Repair(
			$this->config,
			$this->rootFolder,
			$this->logger,
			$this->iniGetWrapper,
			$this->createMock(ILockingProvider::class)
		);
		$this->input = $this->getMockBuilder(InputInterface::class)
			->getMock();
		$this->input->expects($this->any())
			->method('getOption')
			->willReturnCallback(function ($parameter) {
				if ($parameter === 'batch') {
					return true;
				}
				return null;
			});
		$this->output = $this->getMockBuilder(OutputInterface::class)
			->setMethods(['section', 'writeln', 'write', 'setVerbosity', 'getVerbosity', 'isQuiet', 'isVerbose', 'isVeryVerbose', 'isDebug', 'setDecorated', 'isDecorated', 'setFormatter', 'getFormatter'])
			->getMock();
		$self = $this;
		$this->output->expects($this->any())
			->method('section')
			->willReturn($this->output);

		/* We need format method to return a string */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$outputFormatter->method('format')->willReturnArgument(0);

		$this->output->expects($this->any())
			->method('getFormatter')
			->willReturn($outputFormatter);
		$this->output->expects($this->any())
			->method('writeln')
			->willReturnCallback(function ($line) use ($self) {
				$self->outputLines .= $line . "\n";
			});
	}

	public function emptyTestDataProvider() {
		/** directoryNames, expectedOutput */
		return [
			[
				[],
				'All previews are already migrated.'
			],
			[
				[['name' => 'a'], ['name' => 'b'], ['name' => 'c']],
				'All previews are already migrated.'
			],
			[
				[['name' => '0', 'content' => ['folder', 'folder']], ['name' => 'b'], ['name' => 'c']],
				'All previews are already migrated.'
			],
			[
				[['name' => '0', 'content' => ['file', 'folder', 'folder']], ['name' => 'b'], ['name' => 'c']],
				'A total of 1 preview files need to be migrated.'
			],
			[
				[['name' => '23'], ['name' => 'b'], ['name' => 'c']],
				'A total of 1 preview files need to be migrated.'
			],
		];
	}

	/**
	 * @dataProvider emptyTestDataProvider
	 */
	public function testEmptyExecute($directoryNames, $expectedOutput): void {
		$previewFolder = $this->getMockBuilder(Folder::class)
			->getMock();
		$directories = array_map(function ($element) {
			$dir = $this->getMockBuilder(Folder::class)
				->getMock();
			$dir->expects($this->any())
				->method('getName')
				->willReturn($element['name']);
			if (isset($element['content'])) {
				$list = [];
				foreach ($element['content'] as $item) {
					if ($item === 'file') {
						$list[] = $this->getMockBuilder(Node::class)
							->getMock();
					} elseif ($item === 'folder') {
						$list[] = $this->getMockBuilder(Folder::class)
							->getMock();
					}
				}
				$dir->expects($this->once())
					->method('getDirectoryListing')
					->willReturn($list);
			}
			return $dir;
		}, $directoryNames);
		$previewFolder->expects($this->once())
			->method('getDirectoryListing')
			->willReturn($directories);
		$this->rootFolder->expects($this->once())
			->method('get')
			->with('appdata_/preview')
			->willReturn($previewFolder);

		$this->repair->run($this->input, $this->output);

		$this->assertStringContainsString($expectedOutput, $this->outputLines);
	}
}
