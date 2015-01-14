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
class SharingCheckMiddlewareTest extends \Test\TestCase {

	/** @var \OCP\IConfig */
	private $config;
	/** @var \OCP\App\IAppManager */
	private $appManager;
	/** @var SharingCheckMiddleware */
	private $sharingCheckMiddleware;

	protected function setUp() {
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->appManager = $this->getMockBuilder('\OCP\App\IAppManager')
			->disableOriginalConstructor()->getMock();

		$this->sharingCheckMiddleware = new SharingCheckMiddleware('files_sharing', $this->config, $this->appManager);
	}

	public function testIsSharingEnabledWithEverythingEnabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('yes'));

		$this->assertTrue(\Test_Helper::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsSharingEnabledWithAppDisabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(false));

		$this->assertFalse(\Test_Helper::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}

	public function testIsSharingEnabledWithSharingDisabled() {
		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with('files_sharing')
			->will($this->returnValue(true));

		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_allow_links', 'yes')
			->will($this->returnValue('no'));

		$this->assertFalse(\Test_Helper::invokePrivate($this->sharingCheckMiddleware, 'isSharingEnabled'));
	}
}
