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

namespace Tests\Core\Controller;


use OC\Core\Controller\AutoCompleteController;
use OCP\Collaboration\AutoComplete\IManager;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IRequest;
use Test\TestCase;

class AutoCompleteControllerTest extends TestCase {
	/** @var  ISearch|\PHPUnit_Framework_MockObject_MockObject */
	protected $collaboratorSearch;
	/** @var  IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $autoCompleteManager;
	/** @var  AutoCompleteController */
	protected $controller;

	protected function setUp() {
		parent::setUp();

		/** @var IRequest $request */
		$request = $this->createMock(IRequest::class);
		$this->collaboratorSearch = $this->createMock(ISearch::class);
		$this->autoCompleteManager = $this->createMock(IManager::class);

		$this->controller = new AutoCompleteController(
			'core',
			$request,
			$this->collaboratorSearch,
			$this->autoCompleteManager
		);
	}

	public function testGet() {
		$searchResults = [
			'exact' => [
				'users' => [],
				'robots' => [],
			],
			'users' => [
				['label' => 'Alice A.', 'value' => ['shareWith' => 'alice']],
				['label' => 'Bob Y.', 'value' => ['shareWith' => 'bob']],
			],
		];

		$expected = [
			[ 'id' => 'alice', 'label' => 'Alice A.', 'source' => 'users'],
			[ 'id' => 'bob', 'label' => 'Bob Y.', 'source' => 'users'],
		];

		$this->collaboratorSearch->expects($this->once())
			->method('search')
			->willReturn([$searchResults, false]);

		$response = $this->controller->get('files', '42', null);

		$list = $response->getData();
		$this->assertEquals($expected, $list);	// has better error output…
		$this->assertSame($expected, $list);
	}
}
