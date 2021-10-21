<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
namespace OCA\ShareByMail;

use OCA\ShareByMail\Settings\SettingsManager;
use OCP\Capabilities\ICapability;
use OCP\Share\IManager;

class Capabilities implements ICapability {

	/** @var IManager */
	private $manager;

	/** @var SettingsManager */
	private $settingsManager;

	public function __construct(IManager $manager,
								SettingsManager $settingsManager) {
		$this->manager = $manager;
		$this->settingsManager = $settingsManager;
	}

	public function getCapabilities(): array {
		return [
			'files_sharing' =>
				[
					'sharebymail' =>
						[
							'enabled' => $this->manager->shareApiAllowLinks(),
							'send_password_by_mail' => $this->settingsManager->sendPasswordByMail(),
							'upload_files_drop' => [
								'enabled' => true,
							],
							'password' => [
								'enabled' => true,
								'enforced' => $this->manager->shareApiLinkEnforcePassword(),
							],
							'expire_date' => [
								'enabled' => true,
								'enforced' => $this->manager->shareApiLinkDefaultExpireDateEnforced(),
							],
						]
				]
		];
	}
}
