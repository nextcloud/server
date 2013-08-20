<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\AppFramework\Http;


class RequestTest extends \PHPUnit_Framework_TestCase {

	public function testRequestAccessors() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
		);

		$request = new Request($vars);

		// Countable
		$this->assertEquals(2, count($request));
		// Array access
		$this->assertEquals('Joey', $request['nickname']);
		// "Magic" accessors
		$this->assertEquals('Joey', $request->{'nickname'});
		$this->assertTrue(isset($request['nickname']));
		$this->assertTrue(isset($request->{'nickname'}));
		$this->assertEquals(false, isset($request->{'flickname'}));
		// Only testing 'get', but same approach for post, files etc.
		$this->assertEquals('Joey', $request->get['nickname']);
		// Always returns null if variable not set.
		$this->assertEquals(null, $request->{'flickname'});
	}

	// urlParams has precedence over POST which has precedence over GET
	public function testPrecedence() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'post' => array('name' => 'Jane Doe', 'nickname' => 'Janey'),
			'urlParams' => array('user' => 'jw', 'name' => 'Johnny Weissmüller'),
		);

		$request = new Request($vars);

		$this->assertEquals(3, count($request));
		$this->assertEquals('Janey', $request->{'nickname'});
		$this->assertEquals('Johnny Weissmüller', $request->{'name'});
	}


	/**
	* @expectedException RuntimeException
	*/
	public function testImmutableArrayAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
		);

		$request = new Request($vars);
		$request['nickname'] = 'Janey';
	}

	/**
	* @expectedException RuntimeException
	*/
	public function testImmutableMagicAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
		);

		$request = new Request($vars);
		$request->{'nickname'} = 'Janey';
	}

}
