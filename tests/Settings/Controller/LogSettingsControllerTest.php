<?php
/**
 * @author Georg Ehrke
 * @copyright 2014 Georg Ehrke <georg@ownCloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Tests\Settings\Controller;

use \OC\Settings\Application;
use OC\Settings\Controller\LogSettingsController;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;

/**
 * @package Tests\Settings\Controller
 */
class LogSettingsControllerTest extends \Test\TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var LogSettingsController */
	private $logSettingsController;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$l = $this->createMock(IL10N::class);
		$l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));
		$this->logSettingsController = new LogSettingsController(
			'settings',
			$this->createMock(IRequest::class),
			$this->config,
			$l
		);
	}

	/**
	 * @dataProvider logLevelData
	 */
	public function testSetLogLevel($level, $inRange) {
		if ($inRange) {
			$this->config->expects($this->once())
				->method('setSystemValue')
				->with('loglevel', $level);
		}

		$response = $this->logSettingsController->setLogLevel($level)->getData();

		if ($inRange) {
			$expectedResponse = ['level' => $level];
		} else {
			$expectedResponse = ['message' => 'log-level out of allowed range'];
		}

		$this->assertSame($expectedResponse, $response);
	}

	public function logLevelData() {
		return [
			[-1, false],
			[0, true],
			[1, true],
			[2, true],
			[3, true],
			[4, true],
			[5, false],
		];
	}

	public function testDownload() {
		$response = $this->logSettingsController->download();

		$this->assertInstanceOf('\OCP\AppFramework\Http\StreamResponse', $response);
		$headers = $response->getHeaders();
		$this->assertEquals('application/octet-stream', $headers['Content-Type']);
		$this->assertEquals('attachment; filename="nextcloud.log"', $headers['Content-Disposition']);
	}
}
