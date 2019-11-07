<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Settings\Tests\Middleware;

use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OC\AppFramework\Utility\ControllerMethodReflector;
use OCA\Settings\Middleware\SubadminMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;

/**
 * Verifies whether an user has at least subadmin rights.
 * To bypass use the `@NoSubadminRequired` annotation
 *
 * @package Tests\Settings\Middleware
 */
class SubadminMiddlewareTest extends \Test\TestCase {
	/** @var SubadminMiddleware */
	private $subadminMiddlewareAsSubAdmin;
	/** @var SubadminMiddleware */
	private $subadminMiddleware;
	/** @var ControllerMethodReflector */
	private $reflector;
	/** @var Controller */
	private $controller;
	/** @var IL10N */
	private $l10n;

	protected function setUp() {
		parent::setUp();
		$this->reflector = $this->getMockBuilder(ControllerMethodReflector::class)
			->disableOriginalConstructor()->getMock();
		$this->controller = $this->getMockBuilder(Controller::class)
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->createMock(IL10N::class);

		$this->subadminMiddlewareAsSubAdmin = new SubadminMiddleware($this->reflector, true, $this->l10n);
		$this->subadminMiddleware = new SubadminMiddleware($this->reflector, false, $this->l10n);
	}

	/**
	 * @expectedException \OC\AppFramework\Middleware\Security\Exceptions\NotAdminException
	 */
	public function testBeforeControllerAsUserWithExemption() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubadminRequired')
			->will($this->returnValue(false));
		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}


	public function testBeforeControllerAsUserWithoutExemption() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubadminRequired')
			->will($this->returnValue(true));
		$this->subadminMiddleware->beforeController($this->controller, 'foo');
	}

	public function testBeforeControllerAsSubAdminWithoutExemption() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubadminRequired')
			->will($this->returnValue(false));
		$this->subadminMiddlewareAsSubAdmin->beforeController($this->controller, 'foo');
	}

	public function testBeforeControllerAsSubAdminWithExemption() {
		$this->reflector
			->expects($this->once())
			->method('hasAnnotation')
			->with('NoSubadminRequired')
			->will($this->returnValue(true));
		$this->subadminMiddlewareAsSubAdmin->beforeController($this->controller, 'foo');
	}

	public function testAfterNotAdminException() {
		$expectedResponse = new TemplateResponse('core', '403', array(), 'guest');
		$expectedResponse->setStatus(403);
		$this->assertEquals($expectedResponse, $this->subadminMiddleware->afterException($this->controller, 'foo', new NotAdminException('')));
	}

	/**
	 * @expectedException \Exception
	 */
	public function testAfterRegularException() {
		$expectedResponse = new TemplateResponse('core', '403', array(), 'guest');
		$expectedResponse->setStatus(403);
		$this->subadminMiddleware->afterException($this->controller, 'foo', new \Exception());
	}
}
