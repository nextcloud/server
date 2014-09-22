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

	/**
	 * @var \OC\Config
	 */
	private $config;

	function setUp() {
		file_put_contents(self::CONFIG_FILE, self::TESTCONTENT);
		$this->config = new OC\Config(self::CONFIG_DIR);
	}

	public function testReadData() {
		$config = new OC\Config('/non-existing');
		$this->assertAttributeEquals(array(), 'cache', $config);

		$this->assertAttributeEquals(array('foo' => 'bar'), 'cache', $this->config);
	}

	public function testGetKeys() {
		$this->assertEquals(array('foo'), $this->config->getKeys());
	}

	public function testGetValue() {
		$this->assertEquals('bar', $this->config->getValue('foo'));
		$this->assertEquals(null, $this->config->getValue('bar'));
		$this->assertEquals('moo', $this->config->getValue('bar', 'moo'));
	}

	public function testSetValue() {
		$this->config->setDebugMode(false);
		$this->config->setValue('foo', 'moo');
		$this->assertAttributeEquals(array('foo' => 'moo'), 'cache', $this->config);
		$content = file_get_contents(self::CONFIG_FILE);

		$expected = "<?php\n\$CONFIG = array (\n  'foo' => 'moo',\n);\n";
		$this->assertEquals($expected, $content);
		$this->config->setValue('bar', 'red');
		$this->assertAttributeEquals(array('foo' => 'moo', 'bar' => 'red'), 'cache', $this->config);
		$content = file_get_contents(self::CONFIG_FILE);

		$expected = "<?php\n\$CONFIG = array (\n  'foo' => 'moo',\n  'bar' => 'red',\n);\n";
		$this->assertEquals($expected, $content);
	}

	public function testDeleteKey() {
		$this->config->setDebugMode(false);
		$this->config->deleteKey('foo');
		$this->assertAttributeEquals(array(), 'cache', $this->config);
		$content = file_get_contents(self::CONFIG_FILE);

		$expected = "<?php\n\$CONFIG = array (\n);\n";
		$this->assertEquals($expected, $content);
	}

	public function testSavingDebugMode() {
		$this->config->setDebugMode(true);
		$this->config->deleteKey('foo'); // change something so we save to the config file
		$this->assertAttributeEquals(array(), 'cache', $this->config);
		$this->assertAttributeEquals(true, 'debugMode', $this->config);
		$content = file_get_contents(self::CONFIG_FILE);

		$expected = "<?php\ndefine('DEBUG',true);\n\$CONFIG = array (\n);\n";
		$this->assertEquals($expected, $content);
	}

	/**
	 * @expectedException \OC\HintException
	 */
	public function testWriteData() {
		if (\OC_Util::runningOnWindows()) {
			throw new \OC\HintException('this is ireelevent for windows');
		}
		$config = new OC\Config('/non-writable');
		$config->setValue('foo', 'bar');
	}
}
