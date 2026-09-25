<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Repair;

use OC\Repair\ClearFrontendCaches;
use OC\Template\JSCombiner;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;

class ClearFrontendCachesTest extends \Test\TestCase {

	private IOutput&MockObject $outputMock;

	protected ClearFrontendCaches $repair;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->outputMock = $this->createMock(IOutput::class);

		$this->repair = $this->createInstanceWithMocks(ClearFrontendCaches::class);
	}

	public function testRun(): void {
		$imagePathCache = $this->createMock(ICache::class);
		$imagePathCache->expects($this->once())
			->method('clear')
			->with('');
		$this->mocks[JSCombiner::class]->expects($this->once())
			->method('resetCache');
		$this->mocks[ICacheFactory::class]->expects($this->once())
			->method('createDistributed')
			->with('imagePath')
			->willReturn($imagePathCache);

		$this->repair->run($this->outputMock);
	}
}
