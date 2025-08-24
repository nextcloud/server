<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\Wizard;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class Test_Wizard
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests
 */
class WizardTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		//we need to make sure the consts are defined, otherwise tests will fail
		//on systems without php5_ldap
		$ldapConsts = ['LDAP_OPT_PROTOCOL_VERSION',
			'LDAP_OPT_REFERRALS', 'LDAP_OPT_NETWORK_TIMEOUT'];
		foreach ($ldapConsts as $const) {
			if (!defined($const)) {
				define($const, 42);
			}
		}
	}

	private function getWizardAndMocks(): array {
		static $confMethods;

		if (is_null($confMethods)) {
			$confMethods = get_class_methods('\OCA\User_LDAP\Configuration');
		}
		/** @var ILDAPWrapper&MockObject $lw */
		$lw = $this->createMock(ILDAPWrapper::class);

		/** @var Configuration&MockObject $conf */
		$conf = $this->getMockBuilder(Configuration::class)
			->onlyMethods($confMethods)
			->setConstructorArgs(['', true])
			->getMock();

		/** @var Access&MockObject $access */
		$access = $this->createMock(Access::class);

		return [new Wizard($conf, $lw, $access), $conf, $lw, $access];
	}

	private function prepareLdapWrapperForConnections(MockObject $ldap) {
		$ldap->expects($this->once())
			->method('connect')
			//dummy value
			->willReturn(ldap_connect('ldap://example.com'));

		$ldap->expects($this->exactly(3))
			->method('setOption')
			->willReturn(true);

		$ldap->expects($this->once())
			->method('bind')
			->willReturn(true);
	}

	public function testCumulativeSearchOnAttributeLimited(): void {
		[$wizard, $configuration, $ldap] = $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapBase') {
					return ['base'];
				}
				return null;
			});

		$this->prepareLdapWrapperForConnections($ldap);

		$ldap->expects($this->any())
			->method('isResource')
			->willReturn(true);

		$ldap->expects($this->exactly(2))
			->method('search')
			//dummy value, usually invalid
			->willReturn(true);

		$ldap->expects($this->exactly(2))
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->willReturn(23);

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$ldap->expects($this->exactly(2))
			->method('firstEntry')
			//dummy value, usually invalid
			->willReturn(true);

		$ldap->expects($this->exactly(8))
			->method('nextEntry')
			//dummy value, usually invalid
			->willReturn(true);

		$ldap->expects($this->exactly(10))
			->method('getAttributes')
			//dummy value, usually invalid
			->willReturn(['cn' => ['foo'], 'count' => 1]);

		global $uidnumber;
		$uidnumber = 1;
		$ldap->expects($this->exactly(10))
			->method('getDN')
			//dummy value, usually invalid
			->willReturnCallback(function ($a, $b) {
				global $uidnumber;
				return $uidnumber++;
			});

		// The following expectations are the real test
		$filters = ['f1', 'f2', '*'];
		$wizard->cumulativeSearchOnAttribute($filters, 'cn', 5);
		unset($uidnumber);
	}

	public function testCumulativeSearchOnAttributeUnlimited(): void {
		[$wizard, $configuration, $ldap] = $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapBase') {
					return ['base'];
				}
				return null;
			});

		$this->prepareLdapWrapperForConnections($ldap);

		$ldap->expects($this->any())
			->method('isResource')
			->willReturnCallback(function ($r) {
				if ($r instanceof \LDAP\Connection) {
					return true;
				}
				if ($r % 24 === 0) {
					global $uidnumber;
					$uidnumber++;
					return false;
				}
				return true;
			});

		$ldap->expects($this->exactly(2))
			->method('search')
			//dummy value, usually invalid
			->willReturn(true);

		$ldap->expects($this->exactly(2))
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->willReturn(23);

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$ldap->expects($this->exactly(2))
			->method('firstEntry')
			//dummy value, usually invalid
			->willReturnCallback(function ($r) {
				global $uidnumber;
				return $uidnumber;
			});

		$ldap->expects($this->exactly(46))
			->method('nextEntry')
			//dummy value, usually invalid
			->willReturnCallback(function ($r) {
				global $uidnumber;
				return $uidnumber;
			});

		$ldap->expects($this->exactly(46))
			->method('getAttributes')
			//dummy value, usually invalid
			->willReturn(['cn' => ['foo'], 'count' => 1]);

		global $uidnumber;
		$uidnumber = 1;
		$ldap->expects($this->exactly(46))
			->method('getDN')
			//dummy value, usually invalid
			->willReturnCallback(function ($a, $b) {
				global $uidnumber;
				return $uidnumber++;
			});

		// The following expectations are the real test
		$filters = ['f1', 'f2', '*'];
		$wizard->cumulativeSearchOnAttribute($filters, 'cn', 0);
		unset($uidnumber);
	}

	public function testDetectEmailAttributeAlreadySet(): void {
		[$wizard, $configuration, $ldap, $access]
			= $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapEmailAttribute') {
					return 'myEmailAttribute';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			});

		$access->expects($this->once())
			->method('countUsers')
			->willReturn(42);

		$wizard->detectEmailAttribute();
	}

	public function testDetectEmailAttributeOverrideSet(): void {
		[$wizard, $configuration, $ldap, $access]
			= $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapEmailAttribute') {
					return 'myEmailAttribute';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			});

		$access->expects($this->exactly(3))
			->method('combineFilterWithAnd')
			->willReturnCallback(function ($filterParts) {
				return str_replace('=*', '', array_pop($filterParts));
			});

		$access->expects($this->exactly(3))
			->method('countUsers')
			->willReturnCallback(function ($filter) {
				if ($filter === 'myEmailAttribute') {
					return 0;
				} elseif ($filter === 'mail') {
					return 3;
				} elseif ($filter === 'mailPrimaryAddress') {
					return 17;
				}
				throw new \Exception('Untested filter: ' . $filter);
			});

		$result = $wizard->detectEmailAttribute()->getResultArray();
		$this->assertSame('mailPrimaryAddress',
			$result['changes']['ldap_email_attr']);
	}

	public function testDetectEmailAttributeFind(): void {
		[$wizard, $configuration, $ldap, $access]
			= $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapEmailAttribute') {
					return '';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			});

		$access->expects($this->exactly(2))
			->method('combineFilterWithAnd')
			->willReturnCallback(function ($filterParts) {
				return str_replace('=*', '', array_pop($filterParts));
			});

		$access->expects($this->exactly(2))
			->method('countUsers')
			->willReturnCallback(function ($filter) {
				if ($filter === 'myEmailAttribute') {
					return 0;
				} elseif ($filter === 'mail') {
					return 3;
				} elseif ($filter === 'mailPrimaryAddress') {
					return 17;
				}
				throw new \Exception('Untested filter: ' . $filter);
			});

		$result = $wizard->detectEmailAttribute()->getResultArray();
		$this->assertSame('mailPrimaryAddress',
			$result['changes']['ldap_email_attr']);
	}

	public function testDetectEmailAttributeFindNothing(): void {
		[$wizard, $configuration, $ldap, $access]
			= $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapEmailAttribute') {
					return 'myEmailAttribute';
				} else {
					//for requirement checks
					return 'let me pass';
				}
			});

		$access->expects($this->exactly(3))
			->method('combineFilterWithAnd')
			->willReturnCallback(function ($filterParts) {
				return str_replace('=*', '', array_pop($filterParts));
			});

		$access->expects($this->exactly(3))
			->method('countUsers')
			->willReturnCallback(function ($filter) {
				if ($filter === 'myEmailAttribute') {
					return 0;
				} elseif ($filter === 'mail') {
					return 0;
				} elseif ($filter === 'mailPrimaryAddress') {
					return 0;
				}
				throw new \Exception('Untested filter: ' . $filter);
			});

		$result = $wizard->detectEmailAttribute();
		$this->assertFalse($result->hasChanges());
	}

	public function testCumulativeSearchOnAttributeSkipReadDN(): void {
		// tests that there is no infinite loop, when skipping already processed
		// DNs (they can be returned multiple times for multiple filters )
		[$wizard, $configuration, $ldap] = $this->getWizardAndMocks();

		$configuration->expects($this->any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapBase') {
					return ['base'];
				}
				return null;
			});

		$this->prepareLdapWrapperForConnections($ldap);

		$ldap->expects($this->any())
			->method('isResource')
			->willReturnCallback(function ($res) {
				return (bool)$res;
			});

		$ldap->expects($this->any())
			->method('search')
			//dummy value, usually invalid
			->willReturn(true);

		$ldap->expects($this->any())
			->method('countEntries')
			//an is_resource check will follow, so we need to return a dummy resource
			->willReturn(7);

		//5 DNs per filter means 2x firstEntry and 8x nextEntry
		$ldap->expects($this->any())
			->method('firstEntry')
			//dummy value, usually invalid
			->willReturn(1);

		global $mark;
		$mark = false;
		// entries return order: 1, 2, 3, 4, 4, 5, 6
		$ldap->expects($this->any())
			->method('nextEntry')
			//dummy value, usually invalid
			->willReturnCallback(function ($a, $prev) {
				$current = $prev + 1;
				if ($current === 7) {
					return false;
				}
				global $mark;
				if ($prev === 4 && !$mark) {
					$mark = true;
					return 4;
				}
				return $current;
			});

		$ldap->expects($this->any())
			->method('getAttributes')
			//dummy value, usually invalid
			->willReturnCallback(function ($a, $entry) {
				return ['cn' => [$entry], 'count' => 1];
			});

		$ldap->expects($this->any())
			->method('getDN')
			//dummy value, usually invalid
			->willReturnCallback(function ($a, $b) {
				return $b;
			});

		// The following expectations are the real test
		$filters = ['f1', 'f2', '*'];
		$resultArray = $wizard->cumulativeSearchOnAttribute($filters, 'cn', 0);
		$this->assertCount(6, $resultArray);
		unset($mark);
	}
}
