<?php
/**
 * @author     Andreas Fischer <bantu@phpbb.com>
 * @copyright  MMXII Andreas Fischer
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

abstract class Crypt_Hash_TestCase extends PHPUnit_Framework_TestCase
{
	static public function setUpBeforeClass()
	{
		require_once('Crypt/Hash.php');

		if (!defined('CRYPT_HASH_MODE'))
		{
			define('CRYPT_HASH_MODE', CRYPT_HASH_MODE_INTERNAL);
		}
	}

	public function setUp()
	{
		if (defined('CRYPT_HASH_MODE') && CRYPT_HASH_MODE !== CRYPT_HASH_MODE_INTERNAL)
		{
			$this->markTestSkipped('Skipping test because CRYPT_HASH_MODE is not defined as CRYPT_HASH_MODE_INTERNAL.');
		}
	}

	protected function assertHashesTo(Crypt_Hash $hash, $message, $expected)
	{
		$this->assertEquals(
			strtolower($expected),
			bin2hex($hash->hash($message)),
			sprintf("Failed asserting that '%s' hashes to '%s'.", $message, $expected)
		);
	}

	protected function assertHMACsTo(Crypt_Hash $hash, $key, $message, $expected)
	{
		$hash->setKey($key);

		$this->assertEquals(
			strtolower($expected),
			bin2hex($hash->hash($message)),
			sprintf("Failed asserting that '%s' HMACs to '%s' with key '%s'.", $message, $expected, $key)
		);
	}
}
