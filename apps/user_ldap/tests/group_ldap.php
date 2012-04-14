<?php
/**
* ownCloud
*
* @author Arthur Schiwon
* @copyright 2012 Arthur Schiwon blizzz@owncloud.com
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

class Test_Group_Ldap extends UnitTestCase {
	function setUp(){
		OC_Group::clearBackends();
	}

	function testSingleBackend(){
		OC_Group::useBackend(new OC_GROUP_LDAP());
		$group_ldap = new OC_GROUP_LDAP();

 		$this->assertIsA(OC_Group::getGroups(),gettype(array()));
		$this->assertIsA($group_ldap->getGroups(),gettype(array()));
	}

}
