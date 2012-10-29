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

require_once("lib/template.php");

class Test_TemplateFunctions extends UnitTestCase {

	public function testP(){
		// FIXME: do we need more testcases?
		$htmlString = "<script>alert('xss');</script>";
		ob_start();
		p($htmlString);
		$result = ob_get_clean();

		$this->assertEqual($result, "&lt;script&gt;alert(&#039;xss&#039;);&lt;/script&gt;");

		ob_end_clean();
		$normalString = "This is a good string!";
		ob_start();
		p($normalString);
		$result = ob_get_clean();

		$this->assertEqual($result, "This is a good string!");

	}


	public function testPrintUnescaped(){
		$htmlString = "<script>alert('xss');</script>";

		ob_start();
		print_unescaped($htmlString);
		$result = ob_get_clean();

		$this->assertEqual($result, $htmlString);

		ob_end_clean();
		$normalString = "This is a good string!";
		ob_start();
		p($normalString);
		$result = ob_get_clean();

		$this->assertEqual($result, "This is a good string!");

	}


}