<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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


namespace OC\AppFramework\Http;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http;


class TemplateResponseTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \OCP\AppFramework\Http\TemplateResponse
	 */
	private $tpl;

	/**
	 * @var \OCP\AppFramework\IApi
	 */
	private $api;

	protected function setUp() {
		$this->api = $this->getMock('OC\AppFramework\Core\API',
								array('getAppName'), array('test'));
		$this->api->expects($this->any())
				->method('getAppName')
				->will($this->returnValue('app'));

		$this->tpl = new TemplateResponse($this->api, 'home');
	}


	public function testSetParams(){
		$params = array('hi' => 'yo');
		$this->tpl->setParams($params);

		$this->assertEquals(array('hi' => 'yo'), $this->tpl->getParams());
	}


	public function testGetTemplateName(){
		$this->assertEquals('home', $this->tpl->getTemplateName());
	}


//	public function testRender(){
//		$ocTpl = $this->getMock('Template', array('fetchPage'));
//		$ocTpl->expects($this->once())
//				->method('fetchPage');
//
//		$tpl = new TemplateResponse('core', 'error');
//
//		$tpl->render();
//	}
//
//
//	public function testRenderAssignsParams(){
//		$params = array('john' => 'doe');
//
//		$tpl = new TemplateResponse('app', 'home');
//		$tpl->setParams($params);
//
//		$tpl->render();
//	}
//
//
//	public function testRenderDifferentApp(){
//
//		$tpl = new TemplateResponse('app', 'home', 'app2');
//
//		$tpl->render();
//	}


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
