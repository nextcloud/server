<?php
/**
 * @author Georg Ehrke
 * @copyright 2014 Georg Ehrke <georg@ownCloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace Test\Settings\Controller;

use \OC\Settings\Application;
use OC\Settings\Controller\LogSettingsController;
use OCP\AppFramework\Http\StreamResponse;

/**
 * @package OC\Settings\Controller
 */
class LogSettingsControllerTest extends \Test\TestCase {

	/** @var \OCP\AppFramework\IAppContainer */
	private $container;

	/** @var LogSettingsController */
	private $logSettingsController;

	protected function setUp() {
		$app = new Application();
		$this->container = $app->getContainer();
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['AppName'] = 'settings';
		$this->logSettingsController = $this->container['LogSettingsController'];
	}

	/**
	 * @dataProvider logLevelData
	 */
	public function testSetLogLevel($level, $inRange) {
		if ($inRange) {
			$this->container['Config']
				->expects($this->once())
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

		$expected = new StreamResponse(\OC_Log_Owncloud::getLogFilePath());
		$expected->addHeader('Content-Type', 'application/octet-stream');
		$expected->addHeader('Content-Disposition', 'attachment; filename="nextcloud.log"');
		$this->assertEquals($expected, $response);
	}
}
