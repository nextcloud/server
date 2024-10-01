<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Comments\Tests\Unit\Collaboration;

use OCA\Comments\Collaboration\CommentersSorter;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use Test\TestCase;

class CommentersSorterTest extends TestCase {
	/** @var ICommentsManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $commentsManager;
	/** @var CommentersSorter */
	protected $sorter;

	protected function setUp(): void {
		parent::setUp();

		$this->commentsManager = $this->createMock(ICommentsManager::class);

		$this->sorter = new CommentersSorter($this->commentsManager);
	}

	/**
	 * @dataProvider sortDataProvider
	 * @param $data
	 */
	public function testSort($data): void {
		$commentMocks = [];
		foreach ($data['actors'] as $actorType => $actors) {
			foreach ($actors as $actorId => $noOfComments) {
				for ($i = 0;$i < $noOfComments;$i++) {
					$mock = $this->createMock(IComment::class);
					$mock->expects($this->atLeastOnce())
						->method('getActorType')
						->willReturn($actorType);
					$mock->expects($this->atLeastOnce())
						->method('getActorId')
						->willReturn($actorId);
					$commentMocks[] = $mock;
				}
			}
		}

		$this->commentsManager->expects($this->once())
			->method('getForObject')
			->willReturn($commentMocks);

		$workArray = $data['input'];
		$this->sorter->sort($workArray, ['itemType' => 'files', 'itemId' => '24']);

		$this->assertEquals($data['expected'], $workArray);
	}

	public function sortDataProvider() {
		return [[
			[
				#1 – sort properly and otherwise keep existing order
				'actors' => ['users' => ['celia' => 3, 'darius' => 7, 'faruk' => 5, 'gail' => 5], 'bots' => ['r2-d2' => 8]],
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
							['value' => ['shareWith' => 'darius']],
							['value' => ['shareWith' => 'faruk']],
							['value' => ['shareWith' => 'gail']],
							['value' => ['shareWith' => 'celia']],
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
				#2 – no commentors, input equals output
				'actors' => [],
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
				'actors' => [],
				'input' => [],
				'expected' => [],
			],
		]];
	}
}
