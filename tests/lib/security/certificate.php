<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
*/

use \OC\Security\Certificate;

class CertificateTest extends \PHPUnit_Framework_TestCase {

	/** @var Certificate That contains a valid certificate */
	protected $goodCertificate;
	/** @var Certificate That contains an invalid certificate */
	protected $invalidCertificate;
	/** @var Certificate That contains an expired certificate */
	protected $expiredCertificate;

	function setUp() {
		$goodCertificate = file_get_contents(__DIR__.'/../../data/certificates/goodCertificate.crt');
		$this->goodCertificate = new Certificate($goodCertificate, 'GoodCertificate');
		$badCertificate = file_get_contents(__DIR__.'/../../data/certificates/badCertificate.crt');
		$this->invalidCertificate = new Certificate($badCertificate, 'BadCertificate');
		$expiredCertificate = file_get_contents(__DIR__.'/../../data/certificates/expiredCertificate.crt');
		$this->expiredCertificate = new Certificate($expiredCertificate, 'ExpiredCertificate');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Certificate could not get parsed.
	 */
	function testBogusData() {
		new Certificate('foo', 'bar');
	}

	function testGetName() {
		$this->assertSame('GoodCertificate', $this->goodCertificate->getName());
		$this->assertSame('BadCertificate', $this->invalidCertificate->getName());
	}

	function testGetCommonName() {
		$this->assertSame('security.owncloud.com', $this->goodCertificate->getCommonName());
		$this->assertSame(null, $this->invalidCertificate->getCommonName());
	}

	function testGetOrganization() {
		$this->assertSame('ownCloud Inc.', $this->goodCertificate->getOrganization());
		$this->assertSame('Internet Widgits Pty Ltd', $this->invalidCertificate->getOrganization());
	}

	function testGetSerial() {
		$this->assertSame('7F:FF:FF:FF:FF:FF:FF:FF', $this->goodCertificate->getSerial());
		$this->assertSame('7F:FF:FF:FF:FF:FF:FF:FF', $this->invalidCertificate->getSerial());
	}

	function testGetIssueDate() {
		$this->assertEquals((new DateTime('2014-08-27 08:45:52 GMT'))->getTimestamp(), $this->goodCertificate->getIssueDate()->getTimestamp());
		$this->assertEquals((new DateTime('2014-08-27 08:48:51 GMT'))->getTimestamp(), $this->invalidCertificate->getIssueDate()->getTimestamp());
	}

	function testGetExpireDate() {
		$this->assertEquals((new DateTime('2015-08-27 08:45:52 GMT'))->getTimestamp(), $this->goodCertificate->getExpireDate()->getTimestamp());
		$this->assertEquals((new DateTime('2015-08-27 08:48:51 GMT'))->getTimestamp(), $this->invalidCertificate->getExpireDate()->getTimestamp());
		$this->assertEquals((new DateTime('2014-08-28 09:12:43 GMT'))->getTimestamp(), $this->expiredCertificate->getExpireDate()->getTimestamp());
	}

	/**
	 * Obviously the following test case might fail after 2015-08-27, just create a new certificate with longer validity then
	 */
	function testIsExpired() {
		$this->assertSame(false, $this->goodCertificate->isExpired());
		$this->assertSame(false, $this->invalidCertificate->isExpired());
		$this->assertSame(true, $this->expiredCertificate->isExpired());
	}

	function testGetIssuerName() {
		$this->assertSame('security.owncloud.com', $this->goodCertificate->getIssuerName());
		$this->assertSame(null, $this->invalidCertificate->getIssuerName());
		$this->assertSame(null, $this->expiredCertificate->getIssuerName());
	}

	function testGetIssuerOrganization() {
		$this->assertSame('ownCloud Inc.', $this->goodCertificate->getIssuerOrganization());
		$this->assertSame('Internet Widgits Pty Ltd', $this->invalidCertificate->getIssuerOrganization());
		$this->assertSame('Internet Widgits Pty Ltd', $this->expiredCertificate->getIssuerOrganization());
	}
}
