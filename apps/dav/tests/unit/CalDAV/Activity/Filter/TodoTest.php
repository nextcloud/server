<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Filter;

use OCA\DAV\CalDAV\Activity\Filter\Todo;
use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;
use Test\TestCase;

class TodoTest extends TestCase {

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $url;

	/** @var IFilter|\PHPUnit\Framework\MockObject\MockObject */
	protected $filter;

	protected function setUp(): void {
		parent::setUp();
		$this->url = $this->createMock(IURLGenerator::class);
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});

		$this->filter = new Todo(
			$l, $this->url
		);
	}

	public function testGetIcon(): void {
		$this->url->expects($this->once())
			->method('imagePath')
			->with('core', 'actions/checkmark.svg')
			->willReturn('path-to-icon');

		$this->url->expects($this->once())
			->method('getAbsoluteURL')
			->with('path-to-icon')
			->willReturn('absolute-path-to-icon');

		$this->assertEquals('absolute-path-to-icon', $this->filter->getIcon());
	}

	public function dataFilterTypes() {
		return [
			[[], []],
			[['calendar_todo'], ['calendar_todo']],
			[['calendar', 'calendar_event', 'calendar_todo'], ['calendar_todo']],
			[['calendar', 'calendar_todo', 'files'], ['calendar_todo']],
		];
	}

	/**
	 * @dataProvider dataFilterTypes
	 * @param string[] $types
	 * @param string[] $expected
	 */
	public function testFilterTypes($types, $expected): void {
		$this->assertEquals($expected, $this->filter->filterTypes($types));
	}
}
