<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Settings;

use OCA\User_LDAP\AppInfo\Application;
use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\Server;
use OCP\Settings\IDelegatedSettings;
use OCP\Template\ITemplateManager;

class Admin implements IDelegatedSettings {
	public function __construct(
		private IL10N $l,
		private ITemplateManager $templateManager,
		private IInitialState $initialState,
	) {
	}

	public function getForm(): TemplateResponse {
		$helper = Server::get(Helper::class);
		$prefixes = $helper->getServerConfigurationPrefixes();
		if (count($prefixes) === 0) {
			$newPrefix = $helper->getNextServerConfigurationPrefix();
			$config = new Configuration($newPrefix, false);
			$config->setConfiguration($config->getDefaults());
			$config->saveConfiguration();
			$prefixes[] = $newPrefix;
		}

		// assign default values
		if (!isset($config)) {
			$config = new Configuration('', false);
		}
		$defaults = $config->getDefaults();
		foreach ($defaults as $key => $default) {
			$parameters[$key . '_default'] = $default;
		}

		$ldapConfigs = [];
		foreach ($prefixes as $prefix) {
			$ldapConfig = new Configuration($prefix);
			$rawLdapConfig = $ldapConfig->getConfiguration();
			foreach ($rawLdapConfig as $key => $value) {
				if (is_array($value)) {
					$rawLdapConfig[$key] = implode(';', $value);
				}
			}

			$ldapConfigs[$prefix] = $rawLdapConfig;
		}

		$this->initialState->provideInitialState('ldapConfigs', $ldapConfigs);
		$this->initialState->provideInitialState('ldapModuleInstalled', function_exists('ldap_connect'));

		\OCP\Util::addStyle(Application::APP_ID, 'settings-admin');
		\OCP\Util::addScript(Application::APP_ID, 'settings-admin');
		return new TemplateResponse(Application::APP_ID, 'settings', $parameters);
	}

	public function getSection(): string {
		return 'ldap';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 5;
	}

	public function getName(): ?string {
		return null; // Only one setting in this section
	}

	public function getAuthorizedAppConfig(): array {
		return []; // Custom controller
	}
}
