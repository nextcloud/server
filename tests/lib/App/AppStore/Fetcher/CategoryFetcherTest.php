<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
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

namespace Test\App\AppStore\Fetcher;

use OC\App\AppStore\Fetcher\CategoryFetcher;

class CategoryFetcherTest extends FetcherBase  {
	public function setUp() {
		parent::setUp();
		$this->fileName = 'categories.json';
		$this->endpoint = 'https://apps.nextcloud.com/api/v1/categories.json';

		$this->fetcher = new CategoryFetcher(
			$this->appData,
			$this->clientService,
			$this->timeFactory,
			$this->config
		);
	}

	public function testAppstoreDisabled() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstoreenabled', true)
			->willReturn(false);
		$this->appData
			->expects($this->never())
			->method('getFolder');

		$this->assertEquals([], $this->fetcher->get());

	}
}
