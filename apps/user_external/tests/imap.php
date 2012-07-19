<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_User_Imap extends UnitTestCase{
	/**
	 * @var OC_User_IMAP $instance
	 */
	private $instance;

	private function getConfig(){
		return include(__DIR__.'/config.php');
	}

	function skip(){
		$config=$this->getConfig();
		$this->skipUnless($config['imap']['run']);
	} 

	function setUp(){
		$config=$this->getConfig();
		$this->instance=new OC_User_IMAP($config['imap']['mailbox']);
	}
	
	function testLogin(){
		$config=$this->getConfig();
		$this->assertEqual($config['imap']['user'],$this->instance->checkPassword($config['imap']['user'],$config['imap']['password']));
		$this->assertFalse($this->instance->checkPassword($config['imap']['user'],$config['imap']['password'].'foo'));
	}
}
