<?php
/**
* ownCloud
*
* @author Thomas Müller
* @copyright 2014 Thomas Müller deepdiver@owncloud.com
*
*/

namespace OCA\user_ldap\tests;

use OCA\user_ldap\lib\Helper;

class Test_Helper extends \PHPUnit_Framework_TestCase {

	public function testTableTruncate() {

		$statement = \OCP\DB::prepare('INSERT INTO `*PREFIX*ldap_user_mapping` (`ldap_dn`, `owncloud_name`, `directory_uuid`) VALUES (?, ?, ?)');
		$statement->execute(array('db01', 'oc1', '000-0000-0000'));
		$statement->execute(array('db02', 'oc2', '000-0000-0001'));

		$statement = \OCP\DB::prepare('SELECT count(*) FROM `*PREFIX*ldap_user_mapping`');
		$result = $statement->execute();
		$this->assertEquals(2, $result->fetchOne());

		Helper::clearMapping('user');

		$result = $statement->execute();
		$this->assertEquals(0, $result->fetchOne());
	}
}
