<?php
/**
 *
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace Test\Settings\Controller;

use OC\Settings\Controller\PersonalController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\L10N\IFactory;

class PersonalControllerTest extends \Test\TestCase {

	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	private $l10nFactory;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var PersonalController */
	private $controller;
	/** @var IL10N */
	private $l;

	public function setUp() {
		parent::setUp();

		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l = $this->createMock(IL10N::class);

		$this->l->method('t')
			->will($this->returnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

		$this->controller = new PersonalController(
			'settings',
			$this->createMock(IRequest::class),
			$this->l10nFactory,
			'user',
			$this->config,
			$this->l
		);
	}

	public function testSetLanguage() {
		$this->l10nFactory->method('findAvailableLanguages')
			->willReturn(['aa', 'bb', 'cc']);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->equalTo('user'),
				$this->equalTo('core'),
				$this->equalTo('lang'),
				$this->equalTo('bb')
			);

		$resp = $this->controller->setLanguage('bb');
		$expected = new JSONResponse([]);
		$this->assertEquals($expected, $resp);
	}

	public function testSetLanguageEn() {
		$this->l10nFactory->method('findAvailableLanguages')
			->willReturn(['aa', 'bb', 'cc']);
		$this->config->expects($this->once())
			->method('setUserValue')
			->with(
				$this->equalTo('user'),
				$this->equalTo('core'),
				$this->equalTo('lang'),
				$this->equalTo('en')
			);

		$resp = $this->controller->setLanguage('en');
		$expected = new JSONResponse([]);
		$this->assertEquals($expected, $resp);
	}

	public function testSetLanguageFails() {
		$this->l10nFactory->method('findAvailableLanguages')
			->willReturn(['aa', 'bb', 'cc']);
		$this->config->expects($this->never())
			->method('setUserValue');

		$resp = $this->controller->setLanguage('dd');
		$expected = new JSONResponse(['message' => 'Invalid request'], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expected, $resp);
	}


	public function testSetLanguageEmpty() {
		$this->l10nFactory->method('findAvailableLanguages')
			->willReturn(['aa', 'bb', 'cc']);
		$this->config->expects($this->never())
			->method('setUserValue');

		$resp = $this->controller->setLanguage('');
		$expected = new JSONResponse(['message' => 'Invalid request'], Http::STATUS_BAD_REQUEST);
		$this->assertEquals($expected, $resp);
	}
}
