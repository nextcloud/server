<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\AppFramework\Http;

global $data;

class RequestTest extends \Test\TestCase {

	protected function setUp() {
		parent::setUp();

		require_once __DIR__ . '/requeststream.php';
		if (in_array('fakeinput', stream_get_wrappers())) {
			stream_wrapper_unregister('fakeinput');
		}
		stream_wrapper_register('fakeinput', 'RequestStream');
		$this->stream = 'fakeinput://data';
	}

	protected function tearDown() {
		stream_wrapper_unregister('fakeinput');
		parent::tearDown();
	}

	public function testRequestAccessors() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request($vars, $this->stream);

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
			'method' => 'GET'
		);

		$request = new Request($vars, $this->stream);

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
			'method' => 'GET'
		);

		$request = new Request($vars, $this->stream);
		$request['nickname'] = 'Janey';
	}

	/**
	* @expectedException RuntimeException
	*/
	public function testImmutableMagicAccess() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET'
		);

		$request = new Request($vars, $this->stream);
		$request->{'nickname'} = 'Janey';
	}

	/**
	* @expectedException LogicException
	*/
	public function testGetTheMethodRight() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request($vars, $this->stream);
		$result = $request->post;
	}

	public function testTheMethodIsRight() {
		$vars = array(
			'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
			'method' => 'GET',
		);

		$request = new Request($vars, $this->stream);
		$this->assertEquals('GET', $request->method);
		$result = $request->get;
		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
	}

	public function testJsonPost() {
		global $data;
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = array(
			'method' => 'POST',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8')
		);

		$request = new Request($vars, $this->stream);
		$this->assertEquals('POST', $request->method);
		$result = $request->post;
		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
		$this->assertEquals('Joey', $request->params['nickname']);
		$this->assertEquals('Joey', $request['nickname']);
	}

	public function testPatch() {
		global $data;
		$data = http_build_query(array('name' => 'John Q. Public', 'nickname' => 'Joey'), '', '&');

		$vars = array(
			'method' => 'PATCH',
			'server' => array('CONTENT_TYPE' => 'application/x-www-form-urlencoded'),
		);

		$request = new Request($vars, $this->stream);

		$this->assertEquals('PATCH', $request->method);
		$result = $request->patch;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);
	}

	public function testJsonPatchAndPut() {
		global $data;

		// PUT content
		$data = '{"name": "John Q. Public", "nickname": "Joey"}';
		$vars = array(
			'method' => 'PUT',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8'),
		);

		$request = new Request($vars, $this->stream);

		$this->assertEquals('PUT', $request->method);
		$result = $request->put;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals('Joey', $result['nickname']);

		// PATCH content
		$data = '{"name": "John Q. Public", "nickname": null}';
		$vars = array(
			'method' => 'PATCH',
			'server' => array('CONTENT_TYPE' => 'application/json; utf-8'),
		);

		$request = new Request($vars, $this->stream);

		$this->assertEquals('PATCH', $request->method);
		$result = $request->patch;

		$this->assertEquals('John Q. Public', $result['name']);
		$this->assertEquals(null, $result['nickname']);
	}

	public function testPutStream() {
		global $data;
		$data = file_get_contents(__DIR__ . '/../../../data/testimage.png');

		$vars = array(
			'put' => $data,
			'method' => 'PUT',
			'server' => array('CONTENT_TYPE' => 'image/png'),
		);

		$request = new Request($vars, $this->stream);
		$this->assertEquals('PUT', $request->method);
		$resource = $request->put;
		$contents = stream_get_contents($resource);
		$this->assertEquals($data, $contents);

		try {
			$resource = $request->put;
		} catch(\LogicException $e) {
			return;
		}
		$this->fail('Expected LogicException.');

	}
}
