<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Search_Result extends PHPUnit_Framework_TestCase {
	public function testConstruct() {
		$result = new \OC_Search_Result("name", "text", "link", "type");
		$this->assertEquals('name', $result->name);
		$this->assertEquals('text', $result->text);
		$this->assertEquals('link', $result->link);
		$this->assertEquals('type', $result->type);
	}
}
