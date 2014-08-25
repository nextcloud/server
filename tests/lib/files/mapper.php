<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller thomas.mueller@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Files;

class Mapper extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \OC\Files\Mapper
	 */
	private $mapper = null;

	public function setUp() {
		$this->mapper = new \OC\Files\Mapper('D:/');
	}

	public function slugifyPathData() {
		return array(
			// with extension
			array('D:/text.txt', 'D:/text.txt'),
			array('D:/text-2.txt', 'D:/text.txt', 2),
			array('D:/a/b/text.txt', 'D:/a/b/text.txt'),

			// without extension
			array('D:/text', 'D:/text'),
			array('D:/text-2', 'D:/text', 2),
			array('D:/a/b/text', 'D:/a/b/text'),

			// with double dot
			array('D:/text.text.txt', 'D:/text.text.txt'),
			array('D:/text.text-2.txt', 'D:/text.text.txt', 2),
			array('D:/a/b/text.text.txt', 'D:/a/b/text.text.txt'),

			// foldername and filename with periods
			array('D:/folder.name.with.periods', 'D:/folder.name.with.periods'),
			array('D:/folder.name.with.periods/test-2.txt', 'D:/folder.name.with.periods/test.txt', 2),
			array('D:/folder.name.with.periods/test.txt', 'D:/folder.name.with.periods/test.txt'),

			// foldername and filename with periods and spaces
			array('D:/folder.name.with.peri-ods', 'D:/folder.name.with.peri ods'),
			array('D:/folder.name.with.peri-ods/te-st-2.t-x-t', 'D:/folder.name.with.peri ods/te st.t x t', 2),
			array('D:/folder.name.with.peri-ods/te-st.t-x-t', 'D:/folder.name.with.peri ods/te st.t x t'),

			/**
			 * If a foldername is empty, after we stripped out some unicode and other characters,
			 * the resulting name must be reproducable otherwise uploading a file into that folder
			 * will not write the file into the same folder.
			 */
			array('D:/' . md5('ありがとう'), 'D:/ありがとう'),
			array('D:/' . md5('ありがとう') . '/issue6722.txt', 'D:/ありがとう/issue6722.txt'),
		);
	}

	/**
	 * @dataProvider slugifyPathData
	 */
	public function testSlugifyPath($slug, $path, $index = null) {
		$this->assertEquals($slug, $this->mapper->slugifyPath($path, $index));
	}
}
