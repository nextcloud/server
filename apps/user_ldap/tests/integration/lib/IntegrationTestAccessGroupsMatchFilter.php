<?php
/**
 * Created by PhpStorm.
 * User: blizzz
 * Date: 26.06.15
 * Time: 18:13
 */

use OCA\user_ldap\lib\LDAP;

require_once __DIR__  . '/../../../../../lib/base.php';

class IntegrationTestAccessGroupsMatchFilter {
	/** @var  LDAP */
	protected $ldap;

	/** @var  \OCA\user_ldap\lib\Connection */
	protected $connection;

	/** @var \OCA\user_ldap\lib\Access */
	protected $access;

	/** @var  string */
	protected $base;

	/** @var string[] */
	protected $server;

	public function __construct($host, $port, $bind, $pwd, $base) {
		$this->base = $base;
		$this->server = [
			'host' => $host,
			'port' => $port,
			'dn'   => $bind,
			'pwd'  => $pwd
		];
	}

	/**
	 * prepares the LDAP environement and sets up a test configuration for
	 * the LDAP backend.
	 */
	public function init() {
		require('setup-scripts/createExplicitUsers.php');
		require('setup-scripts/createExplicitGroups.php');

		$this->initLDAPWrapper();
		$this->initConnection();
		$this->initAccess();
	}

	/**
	 * runs the test cases while outputting progress and result information
	 *
	 * If a test failed, the script is exited with return code 1.
	 */
	public function run() {
		$cases = ['case1', 'case2'];

		foreach ($cases as $case) {
			print("running $case " . PHP_EOL);
			if (!$this->$case()) {
				print(PHP_EOL . '>>> !!! Test ' . $case . ' FAILED !!! <<<' . PHP_EOL . PHP_EOL);
				exit(1);
			}
		}

		print('Tests succeeded' . PHP_EOL);
	}

	/**
	 * tests whether the group filter works with one specific group, while the
	 * input is the same.
	 *
	 * @return bool
	 */
	private function case1() {
		$this->connection->setConfiguration(['ldapGroupFilter' => 'cn=RedGroup']);

		$dns = ['cn=RedGroup,ou=Groups,' . $this->base];
		$result = $this->access->groupsMatchFilter($dns);
		return ($dns === $result);
	}

	/**
	 * Tests whether a filter for limited groups is effective when more existing
	 * groups were passed for validation.
	 *
	 * @return bool
	 */
	private function case2() {
		$this->connection->setConfiguration(['ldapGroupFilter' => '(|(cn=RedGroup)(cn=PurpleGroup))']);

		$dns = [
			'cn=RedGroup,ou=Groups,' . $this->base,
			'cn=BlueGroup,ou=Groups,' . $this->base,
			'cn=PurpleGroup,ou=Groups,' . $this->base
		];
		$result = $this->access->groupsMatchFilter($dns);

		$status =
			count($result) === 2
			&& in_array('cn=RedGroup,ou=Groups,' . $this->base, $result)
			&& in_array('cn=PurpleGroup,ou=Groups,' . $this->base, $result);

		return $status;
	}

	/**
	 * initializes the Access test instance
	 */
	private function initAccess() {
		$this->access = new \OCA\user_ldap\lib\Access($this->connection, $this->ldap, new FakeManager());
	}

	/**
	 * initializes the test LDAP wrapper
	 */
	private function initLDAPWrapper() {
		$this->ldap = new LDAP();
	}

	/**
	 * sets up the LDAP configuration to be used for the test
	 */
	private function initConnection() {
		$this->connection = new \OCA\user_ldap\lib\Connection($this->ldap, '', null);
		$this->connection->setConfiguration([
			'ldapHost' => $this->server['host'],
			'ldapPort' => $this->server['port'],
			'ldapBase' => $this->base,
			'ldapAgentName' => $this->server['dn'],
			'ldapAgentPassword' => $this->server['pwd'],
			'ldapUserFilter' => 'objectclass=inetOrgPerson',
			'ldapUserDisplayName' => 'displayName',
			'ldapGroupDisplayName' => 'cn',
			'ldapLoginFilter' => 'uid=%uid',
			'ldapCacheTTL' => 0,
			'ldapConfigurationActive' => 1,
		]);
	}
}

/**
 * Class FakeManager
 *
 * this is a mock of \OCA\user_ldap\lib\user\Manager which is a dependency of
 * Access, that pulls plenty more things in. Because it is not needed in the
 * scope of these tests, we replace it with a mock.
 */
class FakeManager extends \OCA\user_ldap\lib\user\Manager {
	public function __construct() {}
}

require_once('setup-scripts/config.php');
$test = new IntegrationTestAccessGroupsMatchFilter($host, $port, $adn, $apwd, $bdn);
$test->init();
$test->run();
