<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_User_FTP extends UnitTestCase{
	/**
	 * @var OC_User_IMAP $instance
	 */
	private $instance;
	
	private function getConfig(){
		return include(__DIR__.'/config.php');
	}
	
	function skip(){
		$config=$this->getConfig();
		$this->skipUnless($config['ftp']['run']);
	}
	
	function setUp(){
		$config=$this->getConfig();
		$this->instance=new OC_User_FTP($config['ftp']['host']);
	}
	
	function testLogin(){
		$config=$this->getConfig();
		$this->assertEqual($config['ftp']['user'],$this->instance->checkPassword($config['ftp']['user'],$config['ftp']['password']));
		$this->assertFalse($this->instance->checkPassword($config['ftp']['user'],$config['ftp']['password'].'foo'));
	}
}
