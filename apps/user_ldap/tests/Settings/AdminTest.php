<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Settings;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Template;
use Test\TestCase;

/**
 * @group DB
 * @package OCA\User_LDAP\Tests\Settings
 */
class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var IL10N */
	private $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();

		$this->admin = new Admin(
			$this->l10n
		);
	}

	/**
	 * @UseDB
	 */
	public function testGetForm(): void {
		$prefixes = ['s01'];
		$hosts = ['s01' => ''];

		$wControls = new Template('user_ldap', 'part.wizardcontrols');
		$wControls = $wControls->fetchPage();
		$sControls = new Template('user_ldap', 'part.settingcontrols');
		$sControls = $sControls->fetchPage();

		$parameters['serverConfigurationPrefixes'] = $prefixes;
		$parameters['serverConfigurationHosts'] = $hosts;
		$parameters['settingControls'] = $sControls;
		$parameters['wizardControls'] = $wControls;

		// assign default values
		$config = new Configuration('', false);
		$defaults = $config->getDefaults();
		foreach ($defaults as $key => $default) {
			$parameters[$key . '_default'] = $default;
		}

		$expected = new TemplateResponse('user_ldap', 'settings', $parameters);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('ldap', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(5, $this->admin->getPriority());
	}
}
