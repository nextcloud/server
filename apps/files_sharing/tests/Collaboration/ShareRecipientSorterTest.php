<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing\Tests\Collaboration;


use OCA\Files_Sharing\Collaboration\ShareRecipientSorter;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Share\IManager;
use Test\TestCase;

class ShareRecipientSorterTest extends TestCase {
	/** @var  IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $shareManager;
	/** @var  Folder|\PHPUnit_Framework_MockObject_MockObject */
	protected $userFolder;
	/** @var  ShareRecipientSorter */
	protected $sorter;

	public function setUp() {
		parent::setUp();

		$this->shareManager = $this->createMock(IManager::class);
		$this->userFolder = $this->createMock(Folder::class);

		$this->sorter = new ShareRecipientSorter($this->shareManager, $this->userFolder);
	}

	/**
	 * @dataProvider sortDataProvider
	 * @param $data
	 */
	public function testSort($data) {
		$node = $this->createMock(Node::class);

		if ($data['context']['itemType'] === 'files') {
			$this->userFolder->expects($this->once())
				->method('getById')
				->with($data['context']['itemId'])
				->willReturn([$node]);

			$this->shareManager->expects($this->once())
				->method('getAccessList')
				->with($node)
				->willReturn($data['accessList']);
		} else {
			$this->userFolder->expects($this->never())
				->method('getById');
			$this->shareManager->expects($this->never())
				->method('getAccessList');
		}

		$workArray = $data['input'];
		$this->sorter->sort($workArray, $data['context']);

		$this->assertSame($data['expected'], $workArray);
	}

	public function testSortNoNodes() {
		$this->userFolder->expects($this->once())
			->method('getById')
			->willReturn([]);

		$this->shareManager->expects($this->never())
			->method('getAccessList');

		$originalArray = ['users' => [
					['value' => ['shareWith' => 'alice']],
					['value' => ['shareWith' => 'bob']],
			]];
		$workArray = $originalArray;
		$this->sorter->sort($workArray, ['itemType' => 'files', 'itemId' => 404]);

		$this->assertEquals($originalArray, $workArray);
	}

	public function sortDataProvider() {
		return [[
			[
				#1 – sort properly and otherwise keep existing order
				'context' => ['itemType' => 'files', 'itemId' => 42],
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
				# 2 – no recipients
				'context' => ['itemType' => 'files', 'itemId' => 42],
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
				#3 – unsupported item  type
				'context' => ['itemType' => 'announcements', 'itemId' => 42],
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
				#4 – no nothing
				'context' => ['itemType' => 'files', 'itemId' => 42],
				'accessList' => [],
				'input' => [],
				'expected' => [],
			],
		]];
	}
}
