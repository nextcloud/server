<?php
/**
 * Created by PhpStorm.
 * User: blizzz
 * Date: 15/01/15
 * Time: 17:52
 */

namespace OCA\user_ldap\tests;

use OCA\user_ldap\lib\EnvVariable;
use OCA\user_ldap\lib\EnvVariableFactory;
use Test\TestCase;

class Test_EnvVariable extends TestCase {
	public function runBasicAssertion(EnvVariable $envVar, $key) {
		$envVar->set('robocop123');
		$newValue = getenv($key);
		$this->assertSame('robocop123', $newValue);
	}

	public function testSetAndDestruct() {
		// we test against two keys:
		// one key which usually is not set initially there and one which is
		$keys = array('LDAPTLS_REQCERT', 'LANG');
		foreach($keys as $key) {
			$originalValue = getenv($key);

			$envVar = new EnvVariable($key);
			$this->runBasicAssertion($envVar, $key);

			unset($envVar);
			$resetValue = getenv($key);
			$this->assertSame($originalValue, $resetValue);
		}
	}

	public function testSetAndDestructViaFactory() {
		$factory = new EnvVariableFactory();
		// we test against two keys:
		// one key which usually is not set initially there and one which is
		$keys = array('LDAPTLS_REQCERT', 'LANG');
		foreach($keys as $key) {
			$originalValue = getenv($key);

			$envVar = $factory->get($key);
			$this->runBasicAssertion($envVar, $key);

			unset($envVar);
			$resetValue = getenv($key);
			$this->assertSame($originalValue, $resetValue);
		}
	}
}