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

namespace OC\Settings\Admin;

use OC\Encryption\Manager;
use OCP\IUserManager;
use OCP\Settings\IAdmin;
use OCP\Template;

class Encryption implements IAdmin {
	/** @var Manager */
	private $manager;

	/** @var IUserManager */
	private $userManager;

	public function __construct(Manager $manager, IUserManager $userManager) {
		$this->manager = $manager;
		$this->userManager = $userManager;
	}

	/**
	 * @return Template all parameters are supposed to be assigned
	 */
	public function render() {
		$parameters = [
			// Encryption API
			'encryptionEnabled'       => $this->manager->isEnabled(),
			'encryptionReady'         => $this->manager->isReady(),
			'externalBackendsEnabled' => count($this->userManager->getBackends()) > 1,
		];

		$form = new Template('settings', 'admin/encryption');
		foreach ($parameters as $key => $value) {
			$form->assign($key, $value);
		}
		return $form;
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'encryption';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 0;
	}
}
