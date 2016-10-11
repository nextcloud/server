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


namespace Test\AppFramework\Http;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http;


class TemplateResponseTest extends \Test\TestCase {

	/**
	 * @var \OCP\AppFramework\Http\TemplateResponse
	 */
	private $tpl;

	/**
	 * @var \OCP\AppFramework\IApi
	 */
	private $api;

	protected function setUp() {
		parent::setUp();

		$this->api = $this->getMockBuilder('OC\AppFramework\Core\API')
			->setMethods(['getAppName'])
			->setConstructorArgs(['test'])
			->getMock();
		$this->api->expects($this->any())
				->method('getAppName')
				->will($this->returnValue('app'));

		$this->tpl = new TemplateResponse($this->api, 'home');
	}


	public function testSetParamsConstructor(){
		$params = array('hi' => 'yo');
		$this->tpl = new TemplateResponse($this->api, 'home', $params);

		$this->assertEquals(array('hi' => 'yo'), $this->tpl->getParams());
	}


	public function testSetRenderAsConstructor(){
		$renderAs = 'myrender';
		$this->tpl = new TemplateResponse($this->api, 'home', array(), $renderAs);

		$this->assertEquals($renderAs, $this->tpl->getRenderAs());
	}


	public function testSetParams(){
		$params = array('hi' => 'yo');
		$this->tpl->setParams($params);

		$this->assertEquals(array('hi' => 'yo'), $this->tpl->getParams());
	}


	public function testGetTemplateName(){
		$this->assertEquals('home', $this->tpl->getTemplateName());
	}

	public function testGetRenderAs(){
		$render = 'myrender';
		$this->tpl->renderAs($render);
		$this->assertEquals($render, $this->tpl->getRenderAs());
	}

	public function testChainability() {
		$params = array('hi' => 'yo');
		$this->tpl->setParams($params)
			->setStatus(Http::STATUS_NOT_FOUND);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $this->tpl->getStatus());
		$this->assertEquals(array('hi' => 'yo'), $this->tpl->getParams());
	}

}
