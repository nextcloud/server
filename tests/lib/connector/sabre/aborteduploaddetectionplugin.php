<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_OC_Connector_Sabre_AbortedUploadDetectionPlugin extends PHPUnit_Framework_TestCase {

	/**
	 * @var Sabre_DAV_Server
	 */
	private $server;

	/**
	 * @var OC_Connector_Sabre_AbortedUploadDetectionPlugin
	 */
	private $plugin;

	public function setUp() {
		$this->server = new Sabre_DAV_Server();
		$this->plugin = new OC_Connector_Sabre_AbortedUploadDetectionPlugin();
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider lengthProvider
	 */
	public function testLength($expected, $headers)
	{
		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$length = $this->plugin->getLength();
		$this->assertEquals($expected, $length);
	}

	/**
	 * @dataProvider verifyContentLengthProvider
	 */
	public function testVerifyContentLength($method, $fileSize, $headers)
	{
		$this->plugin->fileView = $this->buildFileViewMock($fileSize);

		$headers['REQUEST_METHOD'] = $method;
		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$this->plugin->verifyContentLength('foo.txt');
		$this->assertTrue(true);
	}

	/**
	 * @dataProvider verifyContentLengthFailedProvider
	 * @expectedException Sabre_DAV_Exception_BadRequest
	 */
	public function testVerifyContentLengthFailed($method, $fileSize, $headers)
	{
		$this->plugin->fileView = $this->buildFileViewMock($fileSize);

		// we expect unlink to be called
		$this->plugin->fileView->expects($this->once())->method('unlink');

		$headers['REQUEST_METHOD'] = $method;
		$this->server->httpRequest = new Sabre_HTTP_Request($headers);
		$this->plugin->verifyContentLength('foo.txt');
	}

	public function verifyContentLengthProvider() {
		return array(
			array('PUT', 1024, array()),
			array('PUT', 1024, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array('PUT', 512, array('HTTP_CONTENT_LENGTH' => '512')),
			array('LOCK', 1024, array()),
			array('LOCK', 1024, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array('LOCK', 512, array('HTTP_CONTENT_LENGTH' => '512')),
		);
	}

	public function verifyContentLengthFailedProvider() {
		return array(
			array('PUT', 1025, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array('PUT', 525, array('HTTP_CONTENT_LENGTH' => '512')),
		);
	}

	public function lengthProvider() {
		return array(
			array(null, array()),
			array(1024, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '1024')),
			array(512, array('HTTP_CONTENT_LENGTH' => '512')),
			array(2048, array('HTTP_X_EXPECTED_ENTITY_LENGTH' => '2048', 'HTTP_CONTENT_LENGTH' => '1024')),
		);
	}

	private function buildFileViewMock($fileSize) {
		// mock filesystem
		$view = $this->getMock('\OC\Files\View', array('filesize', 'unlink'), array(), '', FALSE);
		$view->expects($this->any())->method('filesize')->withAnyParameters()->will($this->returnValue($fileSize));

		return $view;
	}

}
