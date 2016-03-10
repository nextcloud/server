<?php
/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use \OC\Security\TrustedDomainHelper;
use OCP\IConfig;

/**
 * Class TrustedDomainHelperTest
 */
class TrustedDomainHelperTest extends \Test\TestCase {
	/** @var IConfig */
	protected $config;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
	}

	/**
	 * @dataProvider trustedDomainDataProvider
	 * @param string $trustedDomains
	 * @param string $testDomain
	 * @param bool $result
	 */
	public function testIsTrustedDomain($trustedDomains, $testDomain, $result) {
		$this->config->expects($this->once())
			->method('getSystemValue')
			->with('trusted_domains')
			->will($this->returnValue($trustedDomains));

		$trustedDomainHelper = new TrustedDomainHelper($this->config);
		$this->assertEquals($result, $trustedDomainHelper->isTrustedDomain($testDomain));
	}

	/**
	 * @return array
	 */
	public function trustedDomainDataProvider() {
		$trustedHostTestList = [
			'host.one.test',
			'host.two.test',
			'[1fff:0:a88:85a3::ac1f]',
			'host.three.test:443',
		];
		return [
			// empty defaults to false with 8.1
			[null, 'host.one.test:8080', false],
			['', 'host.one.test:8080', false],
			[[], 'host.one.test:8080', false],
			// trust list when defined
			[$trustedHostTestList, 'host.two.test:8080', true],
			[$trustedHostTestList, 'host.two.test:9999', true],
			[$trustedHostTestList, 'host.three.test:8080', false],
			[$trustedHostTestList, 'host.two.test:8080:aa:222', false],
			[$trustedHostTestList, '[1fff:0:a88:85a3::ac1f]', true],
			[$trustedHostTestList, '[1fff:0:a88:85a3::ac1f]:801', true],
			[$trustedHostTestList, '[1fff:0:a88:85a3::ac1f]:801:34', false],
			[$trustedHostTestList, 'host.three.test:443', true],
			[$trustedHostTestList, 'host.three.test:80', false],
			[$trustedHostTestList, 'host.three.test', false],
			// trust localhost regardless of trust list
			[$trustedHostTestList, 'localhost', true],
			[$trustedHostTestList, 'localhost:8080', true],
			[$trustedHostTestList, '127.0.0.1', true],
			[$trustedHostTestList, '127.0.0.1:8080', true],
			// do not trust invalid localhosts
			[$trustedHostTestList, 'localhost:1:2', false],
			[$trustedHostTestList, 'localhost: evil.host', false],
			// do not trust casting
			[[1], '1', false],
		];
	}

}
