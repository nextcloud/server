<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_AutoLoader extends PHPUnit_Framework_TestCase {

	public function testLeadingSlashOnClassName(){
		$this->assertTrue(class_exists('\OC\Files\Storage\Local'));
	}

	public function testNoLeadingSlashOnClassName(){
		$this->assertTrue(class_exists('OC\Files\Storage\Local'));
	}

}
