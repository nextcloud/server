<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace Test\Files\ObjectStore;


use OC\Files\ObjectStore\Mapper;

class MapperTest extends \Test\TestCase {

	public function dataGetBucket() {
		return [
			['user', substr(md5('user'), 0, 3)],
			['USER', substr(md5('USER'), 0, 3)],
			['bc0e8b52-a66c-1035-90c6-d9663bda9e3f', substr(md5('bc0e8b52-a66c-1035-90c6-d9663bda9e3f'), 0, 3)],
		];
	}

	/**
	 * @dataProvider dataGetBucket
	 * @param string $username
	 * @param string $expectedBucket
	 */
	public function testGetBucket($username, $expectedBucket) {
		$user = $this->getMock('OCP\IUser');
		$user->method('getUID')
			->willReturn($username);

		$mapper = new Mapper($user);

		$this->assertSame($expectedBucket, $mapper->getBucket());
	}
}