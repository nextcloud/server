<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Encryption\Settings;

use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use Psr\Log\LoggerInterface;

class Admin implements ISettings {
	public function __construct(
		private IL10N $l,
		private LoggerInterface $logger,
		private IUserSession $userSession,
		private IConfig $config,
		private IUserManager $userManager,
		private ISession $session
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$crypt = new Crypt(
			$this->logger,
			$this->userSession,
			$this->config,
			$this->l);

		$util = new Util(
			new View(),
			$crypt,
			$this->userSession,
			$this->config,
			$this->userManager);

		// Check if an adminRecovery account is enabled for recovering files after lost pwd
		$recoveryAdminEnabled = $this->config->getAppValue('encryption', 'recoveryAdminEnabled', '0');
		$session = new Session($this->session);

		$encryptHomeStorage = $util->shouldEncryptHomeStorage();

		$parameters = [
			'recoveryEnabled' => $recoveryAdminEnabled,
			'initStatus' => $session->getStatus(),
			'encryptHomeStorage' => $encryptHomeStorage,
			'masterKeyEnabled' => $util->isMasterKeyEnabled(),
		];

		return new TemplateResponse('encryption', 'settings-admin', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 11;
	}
}
