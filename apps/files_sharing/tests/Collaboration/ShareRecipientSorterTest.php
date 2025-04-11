<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\Collaboration;

use OCA\Files_Sharing\Collaboration\ShareRecipientSorter;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use Test\TestCase;

class ShareRecipientSorterTest extends TestCase {
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $shareManager;
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	protected $rootFolder;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;
	/** @var ShareRecipientSorter */
	protected $sorter;

	protected function setUp(): void {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->sorter = new ShareRecipientSorter($this->shareManager, $this->rootFolder, $this->userSession);
	}

	/**
	 * @dataProvider sortDataProvider
	 * @param $data
	 */
	public function testSort($data): void {
		$node = $this->createMock(Node::class);

		/** @var Folder|\PHPUnit\Framework\MockObject\MockObject $folder */
		$folder = $this->createMock(Folder::class);
		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->willReturn($folder);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('yvonne');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		if ($data['context']['itemType'] === 'files') {
			$folder->expects($this->once())
				->method('getFirstNodeById')
				->with($data['context']['itemId'])
				->willReturn($node);

			$this->shareManager->expects($this->once())
				->method('getAccessList')
				->with($node)
				->willReturn($data['accessList']);
		} else {
			$folder->expects($this->never())
				->method('getFirstNodeById');
			$this->shareManager->expects($this->never())
				->method('getAccessList');
		}

		$workArray = $data['input'];
		$this->sorter->sort($workArray, $data['context']);

		$this->assertEquals($data['expected'], $workArray);
	}

	public function testSortNoNodes(): void {
		/** @var Folder|\PHPUnit\Framework\MockObject\MockObject $folder */
		$folder = $this->createMock(Folder::class);
		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->willReturn($folder);

		$folder->expects($this->once())
			->method('getFirstNodeById')
			->willReturn(null);

		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('yvonne');

		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->shareManager->expects($this->never())
			->method('getAccessList');

		$originalArray = [
			'users' => [
				['value' => ['shareWith' => 'alice']],
				['value' => ['shareWith' => 'bob']],
			]
		];
		$workArray = $originalArray;
		$this->sorter->sort($workArray, ['itemType' => 'files', 'itemId' => '404']);

		$this->assertEquals($originalArray, $workArray);
	}

	public function sortDataProvider() {
		return [[
			[
				#0 – sort properly and otherwise keep existing order
				'context' => ['itemType' => 'files', 'itemId' => '42'],
				'accessList' => ['users' => ['celia', 'darius', 'faruk', 'gail'], 'bots' => ['r2-d2']],
				'input' => [
					'users' =>
						[
							['value' => ['shareWith' => 'alice']],
							['value' => ['shareWith' => 'bob']],
							['value' => ['shareWith' => 'celia']],
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'elena']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
						],
					'bots' => [
						['value' => ['shareWith' => 'c-3po']],
						['value' => ['shareWith' => 'r2-d2']],
					]
				],
				'expected' => [
					'users' =>
						[
							['value' => ['shareWith' => 'celia']],
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
							['value' => ['shareWith' => 'alice']],
							['value' => ['shareWith' => 'bob']],
							['value' => ['shareWith' => 'elena']],
						],
					'bots' => [
						['value' => ['shareWith' => 'r2-d2']],
						['value' => ['shareWith' => 'c-3po']],
					]
				],
			],
			[
				#1 – no recipients
				'context' => ['itemType' => 'files', 'itemId' => '42'],
				'accessList' => ['users' => false],
				'input' => [
					'users' =>
						[
							['value' => ['shareWith' => 'alice']],
							['value' => ['shareWith' => 'bob']],
							['value' => ['shareWith' => 'celia']],
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'elena']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
						],
					'bots' => [
						['value' => ['shareWith' => 'c-3po']],
						['value' => ['shareWith' => 'r2-d2']],
					]
				],
				'expected' => [
					'users' =>
						[
							['value' => ['shareWith' => 'alice']],
							['value' => ['shareWith' => 'bob']],
							['value' => ['shareWith' => 'celia']],
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'elena']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
						],
					'bots' => [
						['value' => ['shareWith' => 'c-3po']],
						['value' => ['shareWith' => 'r2-d2']],
					]
				],
			],
			[
				#2 – unsupported item  type
				'context' => ['itemType' => 'announcements', 'itemId' => '42'],
				'accessList' => null, // not needed
				'input' => [
					'users' =>
						[
							['value' => ['shareWith' => 'alice']],
							['value' => ['shareWith' => 'bob']],
							['value' => ['shareWith' => 'celia']],
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'elena']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
						],
					'bots' => [
						['value' => ['shareWith' => 'c-3po']],
						['value' => ['shareWith' => 'r2-d2']],
					]
				],
				'expected' => [
					'users' =>
						[
							['value' => ['shareWith' => 'alice']],
							['value' => ['shareWith' => 'bob']],
							['value' => ['shareWith' => 'celia']],
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'elena']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
						],
					'bots' => [
						['value' => ['shareWith' => 'c-3po']],
						['value' => ['shareWith' => 'r2-d2']],
					]
				],
			],
			[
				#3 – no nothing
				'context' => ['itemType' => 'files', 'itemId' => '42'],
				'accessList' => [],
				'input' => [],
				'expected' => [],
			],
		]];
	}
}
