<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Configuration;

class ConfigurationTest extends \Test\TestCase {
	/** @var Configuration */
	protected $configuration;

	protected function setUp(): void {
		parent::setUp();
		$this->configuration = new Configuration('t01', false);
	}

	public function configurationDataProvider() {
		$inputWithDN = array(
			'cn=someUsers,dc=example,dc=org',
			'  ',
			' cn=moreUsers,dc=example,dc=org '
		);
		$expectWithDN = array(
			'cn=someUsers,dc=example,dc=org',
			'cn=moreUsers,dc=example,dc=org'
		);

		$inputNames = array(
			'  uid  ',
			'cn ',
			' ',
			'',
			' whats my name',
		    '	'
		);
		$expectedNames = array('uid', 'cn', 'whats my name');

		$inputString = ' alea iacta est ';
		$expectedString = 'alea iacta est';

		$inputHomeFolder = array(
			' homeDirectory ',
			' attr:homeDirectory ',
			' '
		);

		$expectedHomeFolder = array(
			'attr:homeDirectory', 'attr:homeDirectory', ''
		);

		$password = ' such a passw0rd ';

		return array(
			'set general base' => array('ldapBase', $inputWithDN, $expectWithDN),
			'set user base'    => array('ldapBaseUsers', $inputWithDN, $expectWithDN),
			'set group base'   => array('ldapBaseGroups', $inputWithDN, $expectWithDN),

			'set search attributes users'  => array('ldapAttributesForUserSearch', $inputNames, $expectedNames),
			'set search attributes groups' => array('ldapAttributesForGroupSearch', $inputNames, $expectedNames),

			'set user filter objectclasses'  => array('ldapUserFilterObjectclass', $inputNames, $expectedNames),
			'set user filter groups'         => array('ldapUserFilterGroups', $inputNames, $expectedNames),
			'set group filter objectclasses' => array('ldapGroupFilterObjectclass', $inputNames, $expectedNames),
			'set group filter groups'        => array('ldapGroupFilterGroups', $inputNames, $expectedNames),
			'set login filter attributes'    => array('ldapLoginFilterAttributes', $inputNames, $expectedNames),

			'set agent password' => array('ldapAgentPassword', $password, $password),

			'set home folder, variant 1' => array('homeFolderNamingRule', $inputHomeFolder[0], $expectedHomeFolder[0]),
			'set home folder, variant 2' => array('homeFolderNamingRule', $inputHomeFolder[1], $expectedHomeFolder[1]),
			'set home folder, empty'     => array('homeFolderNamingRule', $inputHomeFolder[2], $expectedHomeFolder[2]),

			// default behaviour, one case is enough, special needs must be tested
			// individually
			'set string value' => array('ldapHost', $inputString, $expectedString),

			'set avatar rule, default' => ['ldapUserAvatarRule', 'default', 'default'],
			'set avatar rule, none' => ['ldapUserAvatarRule', 'none', 'none'],
			'set avatar rule, data attribute' => ['ldapUserAvatarRule', 'data:jpegPhoto', 'data:jpegPhoto'],

			'set external storage home attribute' => ['ldapExtStorageHomeAttribute', 'homePath', 'homePath'],
		);
	}

	/**
	 * @dataProvider configurationDataProvider
	 */
	public function testSetValue($key, $input, $expected) {
		$this->configuration->setConfiguration([$key => $input]);
		$this->assertSame($this->configuration->$key, $expected);
	}

	public function avatarRuleValueProvider() {
		return [
			['none', []],
			['data:selfie', ['selfie']],
			['data:sELFie', ['selfie']],
			['data:', ['jpegphoto', 'thumbnailphoto']],
			['default', ['jpegphoto', 'thumbnailphoto']],
			['invalid#', ['jpegphoto', 'thumbnailphoto']],
		];
	}

	/**
	 * @dataProvider avatarRuleValueProvider
	 */
	public function testGetAvatarAttributes($setting, $expected) {
		$this->configuration->setConfiguration(['ldapUserAvatarRule' => $setting]);
		$this->assertSame($expected, $this->configuration->getAvatarAttributes());
	}

	/**
	 * @dataProvider avatarRuleValueProvider
	 */
	public function testResolveRule($setting, $expected) {
		$this->configuration->setConfiguration(['ldapUserAvatarRule' => $setting]);
		// so far the only thing that can get resolved :)
		$this->assertSame($expected, $this->configuration->resolveRule('avatar'));
	}

}
