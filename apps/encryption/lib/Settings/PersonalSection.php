<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\Encryption\Settings;


use OCA\Encryption\AppInfo\Application;
use OCA\Encryption\Session;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Settings\IIconSection;

class PersonalSection implements IIconSection {

	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l;
	/** @var Application */
	private $app;
	/** @var IConfig */
	private $config;
	/** @var Session */
	private $session;
	/** @var IUserSession */
	private $userSession;

	public function __construct(
		IURLGenerator $urlGenerator,
		IL10N $l,
		Application $app,
		IConfig $config,
		Session $session,
		IUserSession $userSession
	) {
		$this->urlGenerator = $urlGenerator;
		$this->l = $l;
		$this->app = $app;
		$this->config = $config;
		$this->session = $session;
		$this->userSession = $userSession;
	}

	/**
	 * returns the relative path to an 16*16 icon describing the section.
	 * e.g. '/core/img/places/files.svg'
	 *
	 * @returns string
	 * @since 13.0.0
	 */
	public function getIcon() {
		return $this->urlGenerator->imagePath('settings', 'password.svg');
	}

	/**
	 * returns the ID of the section. It is supposed to be a lower case string,
	 * e.g. 'ldap'
	 *
	 * @returns string
	 * @since 9.1
	 */
	public function getID() {
		// we need to return the proper id while installing/upgrading the app
		$loggedIn = $this->userSession->isLoggedIn();

		$recoveryAdminEnabled = $this->config->getAppValue('encryption', 'recoveryAdminEnabled');
		$privateKeySet = $this->session->isPrivateKeySet();

		if ($loggedIn && !$recoveryAdminEnabled && $privateKeySet) {
			return null;
		}
		return 'encryption';
	}

	/**
	 * returns the translated name as it should be displayed, e.g. 'LDAP / AD
	 * integration'. Use the L10N service to translate it.
	 *
	 * @return string
	 * @since 9.1
	 */
	public function getName() {
		return $this->l->t('Encryption');
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the settings navigation. The sections are arranged in ascending order of
	 * the priority values. It is required to return a value between 0 and 99.
	 *
	 * E.g.: 70
	 * @since 9.1
	 */
	public function getPriority() {
		return 10;
	}
}
