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

/**
 * @package Tests\Settings\Controller
 */
class SecuritySettingsControllerTest extends \PHPUnit_Framework_TestCase {

	/** @var \OCP\AppFramework\IAppContainer */
	private $container;

	/** @var SecuritySettingsController */
	private $securitySettingsController;

	protected function setUp() {
		$app = new Application();
		$this->container = $app->getContainer();
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['AppName'] = 'settings';
		$this->securitySettingsController = $this->container['SecuritySettingsController'];
	}

	public function testTrustedDomainsWithExistingValues() {
		$this->container['Config']
			->expects($this->once())
			->method('setSystemValue')
			->with('trusted_domains', array('owncloud.org', 'owncloud.com', 'newdomain.com'));
		$this->container['Config']
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_domains')
			->will($this->returnValue(array('owncloud.org', 'owncloud.com')));

		$response = $this->securitySettingsController->trustedDomains('newdomain.com');
		$expectedResponse = array('status' => 'success');

		$this->assertSame($expectedResponse, $response);
	}

	public function testTrustedDomainsEmpty() {
		$this->container['Config']
			->expects($this->once())
			->method('setSystemValue')
			->with('trusted_domains', array('newdomain.com'));
		$this->container['Config']
			->expects($this->once())
			->method('getSystemValue')
			->with('trusted_domains')
			->will($this->returnValue(''));

		$response = $this->securitySettingsController->trustedDomains('newdomain.com');
		$expectedResponse = array('status' => 'success');

		$this->assertSame($expectedResponse, $response);
	}
}
