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

class Test_User_Database extends Test_User_Backend {
	private $user=array();
	/**
	 * get a new unique user name
	 * test cases can override this in order to clean up created user
	 * @return array
	 */
	public function getUser(){
		$user=uniqid('test_');
		$this->users[]=$user;
		return $user;
	}
	
	public function setUp(){
		$this->backend=new OC_User_Dummy();
	}
	
	public function tearDown(){
		foreach($this->users as $user){
			$this->backend->deleteUser($user);
		}
	}
}
