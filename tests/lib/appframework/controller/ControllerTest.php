<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCP\AppFramework;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;


class ChildController extends Controller {

	public function __construct($appName, $request) {
		parent::__construct($appName, $request);
		$this->registerResponder('tom', function ($respone) {
			return 'hi';
		});
	}

	public function custom($in) {
		$this->registerResponder('json', function ($response) {
			return new JSONResponse(array(strlen($response)));
		});

		return $in;
	}
};

class ControllerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Controller
	 */
	private $controller;
	private $app;

	protected function setUp(){
		$request = new Request(
			array(
				'get' => array('name' => 'John Q. Public', 'nickname' => 'Joey'),
				'post' => array('name' => 'Jane Doe', 'nickname' => 'Janey'),
				'urlParams' => array('name' => 'Johnny Weissmüller'),
				'files' => array('file' => 'filevalue'),
				'env' => array('PATH' => 'daheim'),
				'session' => array('sezession' => 'kein'),
				'method' => 'hi',
			)
		);

		$this->app = $this->getMock('OC\AppFramework\DependencyInjection\DIContainer',
									array('getAppName'), array('test'));
		$this->app->expects($this->any())
				->method('getAppName')
				->will($this->returnValue('apptemplate_advanced'));

		$this->controller = new ChildController($this->app, $request);
	}


	public function testParamsGet(){
		$this->assertEquals('Johnny Weissmüller', $this->controller->params('name', 'Tarzan'));
	}


	public function testParamsGetDefault(){
		$this->assertEquals('Tarzan', $this->controller->params('Ape Man', 'Tarzan'));
	}


	public function testParamsFile(){
		$this->assertEquals('filevalue', $this->controller->params('file', 'filevalue'));
	}


	public function testGetUploadedFile(){
		$this->assertEquals('filevalue', $this->controller->getUploadedFile('file'));
	}



	public function testGetUploadedFileDefault(){
		$this->assertEquals('default', $this->controller->params('files', 'default'));
	}


	public function testGetParams(){
		$params = array(
				'name' => 'Johnny Weissmüller',
				'nickname' => 'Janey',
			);

		$this->assertEquals($params, $this->controller->getParams());
	}


	public function testRender(){
		$this->assertTrue($this->controller->render('') instanceof TemplateResponse);
	}


	public function testSetParams(){
		$params = array('john' => 'foo');
		$response = $this->controller->render('home', $params);

		$this->assertEquals($params, $response->getParams());
	}


	public function testRenderHeaders(){
		$headers = array('one', 'two');
		$response = $this->controller->render('', array(), '', $headers);

		$this->assertTrue(in_array($headers[0], $response->getHeaders()));
		$this->assertTrue(in_array($headers[1], $response->getHeaders()));
	}


	public function testGetRequestMethod(){
		$this->assertEquals('hi', $this->controller->method());
	}


	public function testGetEnvVariable(){
		$this->assertEquals('daheim', $this->controller->env('PATH'));
	}


	/**
	 * @expectedException \DomainException
	 */
	public function testFormatResonseInvalidFormat() {
		$this->controller->buildResponse(null, 'test');
	}


	public function testFormat() {
		$response = $this->controller->buildResponse(array('hi'), 'json');

		$this->assertEquals(array('hi'), $response->getData());
	}


	public function testCustomFormatter() {
		$response = $this->controller->custom('hi');
		$response = $this->controller->buildResponse($response, 'json');

		$this->assertEquals(array(2), $response->getData());
	}


	public function testDefaultResponderToJSON() {
		$responder = $this->controller->getResponderByHTTPHeader('*/*');

		$this->assertEquals('json', $responder);
	}


	public function testResponderAcceptHeaderParsed() {
		$responder = $this->controller->getResponderByHTTPHeader(
			'*/*, application/tom, application/json'
		);

		$this->assertEquals('tom', $responder);
	}


	public function testResponderAcceptHeaderParsedUpperCase() {
		$responder = $this->controller->getResponderByHTTPHeader(
			'*/*, apPlication/ToM, application/json'
		);

		$this->assertEquals('tom', $responder);
	}


}
