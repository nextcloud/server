<?php
/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2012 Robin Appelman icewind@owncloud.com
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

class Test_Cache_APC extends Test_Cache {
	public function setUp() {
		if(!extension_loaded('apc')) {
			$this->markTestSkipped('The apc extension is not available.');
			return;
		}
		if(!ini_get('apc.enable_cli') && OC::$CLI) {
			$this->markTestSkipped('apc not available in CLI.');
			return;
		}
		$this->instance=new OC_Cache_APC();
	}
}
