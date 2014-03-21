<?php
/**
* ownCloud
*
* @author Arthur Schiwon
* @copyright 2014 Arthur Schiwon blizzz@owncloud.com
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

namespace OCA\user_ldap\tests;

use \OCA\user_ldap\lib\Wizard;

// use \OCA\user_ldap\USER_LDAP as UserLDAP;
// use \OCA\user_ldap\lib\Access;
// use \OCA\user_ldap\lib\Configuration;
// use \OCA\user_ldap\lib\ILDAPWrapper;

class Test_Wizard extends \PHPUnit_Framework_TestCase {
	public function setUp() {
		//we need to make sure the consts are defined, otherwise tests will fail
		//on systems without php5_ldap
		$ldapConsts = array('LDAP_OPT_PROTOCOL_VERSION',
							'LDAP_OPT_REFERRALS', 'LDAP_OPT_NETWORK_TIMEOUT');
		foreach($ldapConsts as $const) {
			if(!defined($const)) {
				define($const, 42);
			}
		}
	}

	private function getWizardAndMocks() {
		static $conMethods;

		if(is_null($conMethods)) {
			$conMethods = get_class_methods('\OCA\user_ldap\lib\Configuration');
		}
		$lw   = $this->getMock('\OCA\user_ldap\lib\ILDAPWrapper');
		$conf = $this->getMock('\OCA\user_ldap\lib\Configuration',
							   $conMethods,
							   array($lw, null, null));
		return array(new Wizard($conf, $lw), $conf, $lw);
	}

	private function prepareLdapWrapperForConnections(&$ldap) {
		$ldap->expects($this->once())
			->method('connect')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$ldap->expects($this->exactly(3))
			->method('setOption')
			->will($this->returnValue(true));

		$ldap->expects($this->once())
			->method('bind')
			->will($this->returnValue(true));

	}

	public function testCumulativeSearchOnAttributeLimited() {
		list($wizard, $configuration, $ldap) = $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
					if($name === 'ldapBase') {
						return array('base');
					}
					return null;
			   }));

		$this->prepareLdapWrapperForConnections($ldap);

		$ldap->expects($this->any())
			->method('isResource')
			->will($this->returnValue(true));

		$ldap->expects($this->exactly(2))
			->method('search')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$ldap->expects($this->exactly(2))
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->will($this->returnValue(23));

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$ldap->expects($this->exactly(2))
			->method('firstEntry')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$ldap->expects($this->exactly(8))
			->method('nextEntry')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$ldap->expects($this->exactly(10))
			->method('getAttributes')
			//dummy value, usually invalid
			->will($this->returnValue(array('cn' => array('foo'), 'count' => 1)));

		global $uidnumber;
		$uidnumber = 1;
		$ldap->expects($this->exactly(10))
			->method('getDN')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $b) {
				global $uidnumber;
				return $uidnumber++;
			}));

		# The following expectations are the real test #
		$filters = array('f1', 'f2', '*');
		$wizard->cumulativeSearchOnAttribute($filters, 'cn', true, 5);
		unset($uidnumber);
	}

	public function testCumulativeSearchOnAttributeUnlimited() {
		list($wizard, $configuration, $ldap) = $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
					if($name === 'ldapBase') {
						return array('base');
					}
					return null;
			   }));

		$this->prepareLdapWrapperForConnections($ldap);

		$ldap->expects($this->any())
			->method('isResource')
			->will($this->returnCallback(function($r) {
				if($r === true) {
					return true;
				}
				if($r % 24 === 0) {
					global $uidnumber;
					$uidnumber++;
					return false;
				}
				return true;
			}));

		$ldap->expects($this->exactly(2))
			->method('search')
			//dummy value, usually invalid
			->will($this->returnValue(true));

		$ldap->expects($this->exactly(2))
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->will($this->returnValue(23));

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$ldap->expects($this->exactly(2))
			->method('firstEntry')
			//dummy value, usually invalid
			->will($this->returnCallback(function($r) {
				global $uidnumber;
				return $uidnumber;
			}));

		$ldap->expects($this->exactly(46))
			->method('nextEntry')
			//dummy value, usually invalid
			->will($this->returnCallback(function($r) {
				global $uidnumber;
				return $uidnumber;
			}));

		$ldap->expects($this->exactly(46))
			->method('getAttributes')
			//dummy value, usually invalid
			->will($this->returnValue(array('cn' => array('foo'), 'count' => 1)));

		global $uidnumber;
		$uidnumber = 1;
		$ldap->expects($this->exactly(46))
			->method('getDN')
			//dummy value, usually invalid
			->will($this->returnCallback(function($a, $b) {
				global $uidnumber;
				return $uidnumber++;
			}));

		# The following expectations are the real test #
		$filters = array('f1', 'f2', '*');
		$wizard->cumulativeSearchOnAttribute($filters, 'cn', true, 0);
		unset($uidnumber);
	}

}