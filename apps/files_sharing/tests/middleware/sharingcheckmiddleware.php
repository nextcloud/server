<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @copyright 2014 Lukas Reschke
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Middleware;


/**
 * @package OCA\Files_Sharing\Middleware\SharingCheckMiddleware
 */
class SharingCheckMiddlewareTest extends \PHPUnit_Framework_TestCase {

	/** @var \OCP\IAppConfig */
	private $appConfig;
	/** @var \OCP\AppFramework\IApi */
	private $api;
	/** @var SharingCheckMiddleware */
	private $sharingCheckMiddleware;

	protected function setUp() {
		$this->appConfig = $this->getMockBuilder('\OCP\IAppConfig')
			->disableOriginalConstructor()->getMock();
		$this->api = $this->getMockBuilder('\OCP\AppFramework\IApi')
			->disableOriginalConstructor()->getMock();

		$this->sharingCheckMiddleware = new SharingCheckMiddleware('files_sharing', $this->appConfig, $this->api);
	}

	public function testIsSharingEnabledWithEverythingEnabled() {
		$this->api
			->expects($this->once())
			->method('isAppEnabled')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->appConfig
			->expects($this->once())
			->method('getValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('yes'));

		$this->assertTrue(\Test_Helper::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsSharingEnabledWithAppDisabled() {
		$this->api
			->expects($this->once())
			->method('isAppEnabled')
			->with('files_sharing')
			->will($this->returnValue(false));

		$this->assertFalse(\Test_Helper::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsSharingEnabledWithSharingDisabled() {
		$this->api
			->expects($this->once())
			->method('isAppEnabled')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->appConfig
			->expects($this->once())
			->method('getValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('no'));

		$this->assertFalse(\Test_Helper::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}
}
