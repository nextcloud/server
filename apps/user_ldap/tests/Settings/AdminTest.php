<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\Settings;

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Server;
use OCP\Template\ITemplateManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * @group DB
 * @package OCA\User_LDAP\Tests\Settings
 */
class AdminTest extends TestCase {
	private IL10N&MockObject $l10n;
	private ITemplateManager $templateManager;
	private Admin $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->templateManager = Server::get(ITemplateManager::class);

		$this->admin = new Admin(
			$this->l10n,
			$this->templateManager,
		);
	}

	public function testGetForm(): void {
		$prefixes = ['s01'];
		$hosts = ['s01' => ''];

		$wControls = $this->templateManager->getTemplate('user_ldap', 'part.wizardcontrols');
		$wControls = $wControls->fetchPage();
		$sControls = $this->templateManager->getTemplate('user_ldap', 'part.settingcontrols');
		$sControls = $sControls->fetchPage();

		$parameters = [];
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
