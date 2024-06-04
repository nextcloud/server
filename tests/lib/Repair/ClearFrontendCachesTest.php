<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Template\JSCombiner;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Migration\IOutput;

class ClearFrontendCachesTest extends \Test\TestCase {
	/** @var ICacheFactory */
	private $cacheFactory;

	/** @var JSCombiner */
	private $jsCombiner;

	/** @var \OC\Repair\ClearFrontendCaches */
	protected $repair;

	/** @var IOutput */
	private $outputMock;

	protected function setUp(): void {
		parent::setUp();

		$this->outputMock = $this->createMock(IOutput::class);

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->jsCombiner = $this->createMock(JSCombiner::class);

		$this->repair = new \OC\Repair\ClearFrontendCaches($this->cacheFactory, $this->jsCombiner);
	}


	public function testRun() {
		$imagePathCache = $this->createMock(ICache::class);
		$imagePathCache->expects($this->once())
			->method('clear')
			->with('');
		$this->jsCombiner->expects($this->once())
			->method('resetCache');
		$this->cacheFactory->expects($this->once())
			->method('createDistributed')
			->with('imagePath')
			->willReturn($imagePathCache);

		$this->repair->run($this->outputMock);
		$this->assertTrue(true);
	}
}
