<?php
/**
* ownCloud
*
* @author Bernhard Posselt
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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

OC::autoload('OC_Template');

class Test_TemplateFunctions extends PHPUnit_Framework_TestCase {

	public function testP() {
		// FIXME: do we need more testcases?
		$htmlString = "<script>alert('xss');</script>";
		ob_start();
		p($htmlString);
		$result = ob_get_clean();

		$this->assertEquals("&lt;script&gt;alert(&#039;xss&#039;);&lt;/script&gt;", $result);
	}

	public function testPNormalString() {
		$normalString = "This is a good string!";
		ob_start();
		p($normalString);
		$result = ob_get_clean();

		$this->assertEquals("This is a good string!", $result);
	}


	public function testPrintUnescaped() {
		$htmlString = "<script>alert('xss');</script>";

		ob_start();
		print_unescaped($htmlString);
		$result = ob_get_clean();

		$this->assertEquals($htmlString, $result);
	}

	public function testPrintUnescapedNormalString() {
		$normalString = "This is a good string!";
		ob_start();
		print_unescaped($normalString);
		$result = ob_get_clean();

		$this->assertEquals("This is a good string!", $result);
	}


}
