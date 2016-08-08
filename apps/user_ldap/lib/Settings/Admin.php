<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\Settings;


use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\IL10N;
use OCP\Settings\IAdmin;
use OCP\Template;

class Admin implements IAdmin {

	/** @var IL10N */
	private $l;

	public function __construct(IL10N $l) {
		$this->l = $l;
	}

	/**
	 * @return Template all parameters are supposed to be assigned
	 */
	public function render() {
		$settings = new Template('user_ldap', 'settings');

		$helper = new Helper();
		$prefixes = $helper->getServerConfigurationPrefixes();
		$hosts = $helper->getServerConfigurationHosts();

		$wizardHtml = '';
		$toc = [];

		$wControls = new Template('user_ldap', 'part.wizardcontrols');
		$wControls = $wControls->fetchPage();
		$sControls = new Template('user_ldap', 'part.settingcontrols');
		$sControls = $sControls->fetchPage();

		$wizTabs = [
			['tpl' => 'part.wizard-server',      'cap' => $this->l->t('Server')],
			['tpl' => 'part.wizard-userfilter',  'cap' => $this->l->t('Users')],
			['tpl' => 'part.wizard-loginfilter', 'cap' => $this->l->t('Login Attributes')],
			['tpl' => 'part.wizard-groupfilter', 'cap' => $this->l->t('Groups')],
		];
		$wizTabsCount = count($wizTabs);
		for($i = 0; $i < $wizTabsCount; $i++) {
			$tab = new Template('user_ldap', $wizTabs[$i]['tpl']);
			if($i === 0) {
				$tab->assign('serverConfigurationPrefixes', $prefixes);
				$tab->assign('serverConfigurationHosts', $hosts);
			}
			$tab->assign('wizardControls', $wControls);
			$wizardHtml .= $tab->fetchPage();
			$toc['#ldapWizard'.($i+1)] = $wizTabs[$i]['cap'];
		}

		$settings->assign('tabs', $wizardHtml);
		$settings->assign('toc', $toc);
		$settings->assign('settingControls', $sControls);

		// assign default values
		$config = new Configuration('', false);
		$defaults = $config->getDefaults();
		foreach($defaults as $key => $default) {
			$settings->assign($key.'_default', $default);
		}

		return $settings;
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'ldap';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 5;
	}

	private function renderControls() {
		$controls = new Template('user_ldap', 'part.settingcontrols');
		return $controls->fetchPage();

	}
}
