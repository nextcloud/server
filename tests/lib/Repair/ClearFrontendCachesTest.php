<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
