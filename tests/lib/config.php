<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Config extends PHPUnit_Framework_TestCase {
	const CONFIG_FILE = 'static://config.php';
	const CONFIG_DIR = 'static://';
	const TESTCONTENT = '<?php $CONFIG=array("foo"=>"bar");';

	public function testReadData()
	{
		$config = new OC\Config(self::CONFIG_DIR, false);
		$this->assertAttributeEquals(array(), 'cache', $config);

		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$config = new OC\Config(self::CONFIG_DIR, false);
		$this->assertAttributeEquals(array('foo'=>'bar'), 'cache', $config);
	}

	public function testGetKeys()
	{
		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$config = new OC\Config(self::CONFIG_DIR, false);
		$this->assertEquals(array('foo'), $config->getKeys());
	}

	public function testGetValue()
	{
		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$config = new OC\Config(self::CONFIG_DIR, false);
		$this->assertEquals('bar', $config->getValue('foo'));
		$this->assertEquals(null, $config->getValue('bar'));
		$this->assertEquals('moo', $config->getValue('bar', 'moo'));
	}

	public function testSetValue()
	{
		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$config = new OC\Config(self::CONFIG_DIR, false);
		$config->setValue('foo', 'moo');
		$this->assertAttributeEquals(array('foo'=>'moo'), 'cache', $config);
		$content = file_get_contents(self::CONFIG_FILE);
		$this->assertEquals(<<<EOL
<?php
\$CONFIG = array (
  'foo' => 'moo',
);

EOL
, $content);
		$config->setValue('bar', 'red');
		$this->assertAttributeEquals(array('foo'=>'moo', 'bar'=>'red'), 'cache', $config);
		$content = file_get_contents(self::CONFIG_FILE);
		$this->assertEquals(<<<EOL
<?php
\$CONFIG = array (
  'foo' => 'moo',
  'bar' => 'red',
);

EOL
, $content);
	}

	public function testDeleteKey()
	{
		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$config = new OC\Config(self::CONFIG_DIR, false);
		$config->deleteKey('foo');
		$this->assertAttributeEquals(array(), 'cache', $config);
		$content = file_get_contents(self::CONFIG_FILE);
		$this->assertEquals(<<<EOL
<?php
\$CONFIG = array (
);

EOL
, $content);
	}

	public function testSavingDebugMode()
	{
		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$config = new OC\Config(self::CONFIG_DIR, true);
		$config->deleteKey('foo'); // change something so we save to the config file
		$this->assertAttributeEquals(array(), 'cache', $config);
		$this->assertAttributeEquals(true, 'debug_mode', $config);
		$content = file_get_contents(self::CONFIG_FILE);
		$this->assertEquals(<<<EOL
<?php
define('DEBUG',true);
\$CONFIG = array (
);

EOL
, $content);
	}

	public function testWriteData()
	{
		$config = new OC\Config('/non-writable', false);
		try {
			$config->setValue('foo', 'bar');
		} catch (\OC\HintException $e) {
			return;
		}
		$this->fail();
	}
}
