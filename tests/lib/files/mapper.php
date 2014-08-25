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

	public function testSlugifyPath() {
		// with extension
		$this->assertEquals('D:/text.txt', $this->mapper->slugifyPath('D:/text.txt'));
		$this->assertEquals('D:/text-2.txt', $this->mapper->slugifyPath('D:/text.txt', 2));
		$this->assertEquals('D:/a/b/text.txt', $this->mapper->slugifyPath('D:/a/b/text.txt'));

		// without extension
		$this->assertEquals('D:/text', $this->mapper->slugifyPath('D:/text'));
		$this->assertEquals('D:/text-2', $this->mapper->slugifyPath('D:/text', 2));
		$this->assertEquals('D:/a/b/text', $this->mapper->slugifyPath('D:/a/b/text'));

		// with double dot
		$this->assertEquals('D:/text.text.txt', $this->mapper->slugifyPath('D:/text.text.txt'));
		$this->assertEquals('D:/text.text-2.txt', $this->mapper->slugifyPath('D:/text.text.txt', 2));
		$this->assertEquals('D:/a/b/text.text.txt', $this->mapper->slugifyPath('D:/a/b/text.text.txt'));
			
		// foldername and filename with periods
		$this->assertEquals('D:/folder.name.with.periods', $this->mapper->slugifyPath('D:/folder.name.with.periods'));
		$this->assertEquals('D:/folder.name.with.periods/test-2.txt', $this->mapper->slugifyPath('D:/folder.name.with.periods/test.txt', 2));
		$this->assertEquals('D:/folder.name.with.periods/test.txt', $this->mapper->slugifyPath('D:/folder.name.with.periods/test.txt'));

		// foldername and filename with periods and spaces
		$this->assertEquals('D:/folder.name.with.peri-ods', $this->mapper->slugifyPath('D:/folder.name.with.peri ods'));
		$this->assertEquals('D:/folder.name.with.peri-ods/te-st-2.t-x-t', $this->mapper->slugifyPath('D:/folder.name.with.peri ods/te st.t x t', 2));
		$this->assertEquals('D:/folder.name.with.peri-ods/te-st.t-x-t', $this->mapper->slugifyPath('D:/folder.name.with.peri ods/te st.t x t'));
	}

	/**
	 * If a foldername is empty, after we stripped out some unicode and other characters,
	 * the resulting name must be reproducable otherwise uploading a file into that folder
	 * will not write the file into the same folder.
	 */
	public function slugifyEmptyUnicodeFoldername() {
		// Slugify the folder
		$slugifiedFolder = $this->mapper->slugifyPath('D:/ありがとう');
		$this->assertEquals('D:/' . md5('ありがとう'), $slugifiedFolder);

		// Slugify a file in the folder
		$slugifiedFileInUtf8Folder = $this->mapper->slugifyPath('D:/ありがとう/issue6722.txt');
		$this->assertEquals('D:/' . md5('ありがとう') . '/issue6722.txt', $slugifiedFileInUtf8Folder);
	}
}
