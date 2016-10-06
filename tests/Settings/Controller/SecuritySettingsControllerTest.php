<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace Tests\Settings\Controller;

use \OC\Settings\Application;
use OC\Settings\Controller\SecuritySettingsController;
use OCP\IConfig;
use OCP\IRequest;

/**
 * @package Tests\Settings\Controller
 */
class SecuritySettingsControllerTest extends \Test\TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var SecuritySettingsController */
	private $securitySettingsController;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->securitySettingsController = new SecuritySettingsController(
			'settings',
			$this->createMock(IRequest::class),
			$this->config
		);
	}

	public function testTrustedDomainsWithExistingValues() {
		$this->config
			->expects($this->once())
			->method('setSystemValue')
			->with('trusted_domains', array('owncloud.org', 'owncloud.com', 'newdomain.com'));
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_domains')
			->will($this->returnValue(array('owncloud.org', 'owncloud.com')));

		$response = $this->securitySettingsController->trustedDomains('newdomain.com');
		$expectedResponse = array('status' => 'success');

		$this->assertSame($expectedResponse, $response);
	}

	public function testTrustedDomainsEmpty() {
		$this->config
			->expects($this->once())
			->method('setSystemValue')
			->with('trusted_domains', array('newdomain.com'));
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with($this->equalTo('trusted_domains'), $this->equalTo([]))
			->willReturn([]);

		$response = $this->securitySettingsController->trustedDomains('newdomain.com');
		$expectedResponse = array('status' => 'success');

		$this->assertSame($expectedResponse, $response);
	}
}
