<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Command;

use OCA\Files_Sharing\Command\ListShares;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Test\TestCase;

class ListSharesTest extends TestCase {
	private IManager&MockObject $shareManager;
	private IRootFolder&MockObject $rootFolder;
	private ListShares $command;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->command = new ListShares($this->shareManager, $this->rootFolder);
	}

	private function createShare(int $id, ?Node $node, ?ICacheEntry $cacheEntry = null): IShare {
		$share = $this->createMock(IShare::class);
		$share->method('getId')->willReturn((string)$id);
		$share->method('getNodeId')->willReturn($id * 100);
		$share->method('getTarget')->willReturn("/target-$id");
		$share->method('getShareOwner')->willReturn('owner');
		$share->method('getSharedWith')->willReturn('recipient');
		$share->method('getSharedBy')->willReturn('owner');
		$share->method('getShareType')->willReturn(IShare::TYPE_USER);
		$share->method('getNodeCacheEntry')->willReturn($cacheEntry);
		if ($node === null) {
			$share->method('getNode')->willThrowException(new NotFoundException('Node for share not found'));
		} else {
			$share->method('getNode')->willReturn($node);
		}
		return $share;
	}

	private function createNode(string $path, int $parentId = 0): Node {
		$node = $this->createMock(Node::class);
		$node->method('getPath')->willReturn($path);
		$node->method('getParentId')->willReturn($parentId);
		return $node;
	}

	private function createInput(array $options): InputInterface {
		$options += [
			'owner' => null,
			'recipient' => null,
			'by' => null,
			'file' => null,
			'parent' => null,
			'recursive' => false,
			'type' => null,
			'status' => null,
			'output' => 'json',
		];
		$input = $this->createMock(InputInterface::class);
		$input->method('getOption')
			->willReturnCallback(fn (string $name) => $options[$name] ?? null);
		return $input;
	}

	public function testListIncludesOrphanShare(): void {
		$shares = [
			$this->createShare(1, $this->createNode('/owner/files/document.txt')),
			$this->createShare(2, null),
		];
		$this->shareManager->method('getAllShares')
			->willReturn(new \ArrayIterator($shares));

		$output = new BufferedOutput();
		$exitCode = $this->command->execute($this->createInput([]), $output);

		$this->assertSame(0, $exitCode);
		$rows = json_decode($output->fetch(), true);
		$this->assertCount(2, $rows);
		$this->assertSame('/owner/files/document.txt', $rows[0]['source-path']);
		$this->assertNull($rows[1]['source-path']);
	}

	public function testParentFilterSkipsOrphanShare(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(42);
		$this->rootFolder->method('get')
			->with('/owner/files/folder')
			->willReturn($parent);

		$cacheEntry = $this->createMock(ICacheEntry::class);
		$cacheEntry->method('getParentId')->willReturn(42);
		$shares = [
			$this->createShare(1, $this->createNode('/owner/files/folder/document.txt'), $cacheEntry),
			$this->createShare(2, null),
		];
		$this->shareManager->method('getAllShares')
			->willReturn(new \ArrayIterator($shares));

		$output = new BufferedOutput();
		$exitCode = $this->command->execute($this->createInput(['parent' => '/owner/files/folder']), $output);

		$this->assertSame(0, $exitCode);
		$rows = json_decode($output->fetch(), true);
		$this->assertCount(1, $rows);
		$this->assertSame('1', $rows[0]['id']);
	}

	public function testRecursiveParentFilterSkipsOrphanShare(): void {
		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(42);
		$parent->method('getRelativePath')
			->willReturnCallback(fn (string $path) => str_starts_with($path, '/owner/files/folder/')
				? substr($path, strlen('/owner/files/folder'))
				: null);
		$this->rootFolder->method('get')
			->with('/owner/files/folder')
			->willReturn($parent);

		$shares = [
			$this->createShare(1, $this->createNode('/owner/files/folder/sub/document.txt')),
			$this->createShare(2, null),
		];
		$this->shareManager->method('getAllShares')
			->willReturn(new \ArrayIterator($shares));

		$output = new BufferedOutput();
		$exitCode = $this->command->execute(
			$this->createInput(['parent' => '/owner/files/folder', 'recursive' => true]),
			$output,
		);

		$this->assertSame(0, $exitCode);
		$rows = json_decode($output->fetch(), true);
		$this->assertCount(1, $rows);
		$this->assertSame('1', $rows[0]['id']);
	}
}
