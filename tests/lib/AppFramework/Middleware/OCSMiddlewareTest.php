<?php
/**
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
namespace Test\AppFramework\Middleware;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\OCSMiddleware;


class OCSMiddlewareTest extends \Test\TestCase {

	/**
	 * @var Request
	 */
	private $request;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('OCP\IRequest')
			->getMock();

	}

	public function dataAfterException() {
		$OCSController = $this->getMockBuilder('OCP\AppFramework\OCSController')
			->disableOriginalConstructor()
			->getMock();
		$controller = $this->getMockBuilder('OCP\AppFramework\Controller')
			->disableOriginalConstructor()
			->getMock();

		return [
			[$OCSController, new \Exception(), true],
			[$OCSController, new OCSException(), false, '', Http::STATUS_INTERNAL_SERVER_ERROR],
			[$OCSController, new OCSException('foo'), false, 'foo', Http::STATUS_INTERNAL_SERVER_ERROR],
			[$OCSController, new OCSException('foo', Http::STATUS_IM_A_TEAPOT), false, 'foo', Http::STATUS_IM_A_TEAPOT],
			[$OCSController, new OCSBadRequestException(), false, '', Http::STATUS_BAD_REQUEST],
			[$OCSController, new OCSBadRequestException('foo'), false, 'foo', Http::STATUS_BAD_REQUEST],
			[$OCSController, new OCSForbiddenException(), false, '', Http::STATUS_FORBIDDEN],
			[$OCSController, new OCSForbiddenException('foo'), false, 'foo', Http::STATUS_FORBIDDEN],
			[$OCSController, new OCSNotFoundException(), false, '', Http::STATUS_NOT_FOUND],
			[$OCSController, new OCSNotFoundException('foo'), false, 'foo', Http::STATUS_NOT_FOUND],

			[$controller, new \Exception(), true],
			[$controller, new OCSException(), true],
			[$controller, new OCSException('foo'), true],
			[$controller, new OCSException('foo', Http::STATUS_IM_A_TEAPOT), true],
			[$controller, new OCSBadRequestException(), true],
			[$controller, new OCSBadRequestException('foo'), true],
			[$controller, new OCSForbiddenException(), true],
			[$controller, new OCSForbiddenException('foo'), true],
			[$controller, new OCSNotFoundException(), true],
			[$controller, new OCSNotFoundException('foo'), true],
		];
	}

	/**
	 * @dataProvider dataAfterException
	 *
	 * @param Controller $controller
	 * @param \Exception $exception
	 * @param bool $forward
	 * @param string $message
	 * @param int $code
	 */
	public function testAfterException($controller, $exception, $forward, $message = '', $code = 0) {
		$this->request
			->method('getScriptName')
			->willReturn('/mysubfolder/ocs/v1.php');
		$OCSMiddleware = new OCSMiddleware($this->request);

		try {
			$result = $OCSMiddleware->afterException($controller, 'method', $exception);
			$this->assertFalse($forward);

			$this->assertInstanceOf('OCP\AppFramework\Http\OCSResponse', $result);

			$this->assertSame($message, $this->invokePrivate($result, 'message'));
			$this->assertSame($code, $this->invokePrivate($result, 'statuscode'));
		} catch (\Exception $e) {
			$this->assertTrue($forward);
			$this->assertEquals($exception, $e);
		}
	}

	/**
	 * @dataProvider dataAfterException
	 *
	 * @param Controller $controller
	 * @param \Exception $exception
	 * @param bool $forward
	 * @param string $message
	 * @param int $code
	 */
	public function testAfterExceptionOCSv2SubFolder($controller, $exception, $forward, $message = '', $code = 0) {
		$this->request
			->method('getScriptName')
			->willReturn('/mysubfolder/ocs/v2.php');
		$OCSMiddleware = new OCSMiddleware($this->request);

		try {
			$result = $OCSMiddleware->afterException($controller, 'method', $exception);
			$this->assertFalse($forward);

			$this->assertInstanceOf('OCP\AppFramework\Http\OCSResponse', $result);

			$this->assertSame($message, $this->invokePrivate($result, 'message'));
			$this->assertSame($code, $this->invokePrivate($result, 'statuscode'));
			$this->assertSame($code, $result->getStatus());
		} catch (\Exception $e) {
			$this->assertTrue($forward);
			$this->assertEquals($exception, $e);
		}
	}

}
