<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ShareByMail;

use OCA\ShareByMail\Settings\SettingsManager;
use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\Share\IManager;

class Capabilities implements ICapability {

	/** @var IManager */
	private $manager;

	/** @var SettingsManager */
	private $settingsManager;

	/** @var IAppManager */
	private $appManager;

	public function __construct(IManager $manager,
		SettingsManager $settingsManager,
		IAppManager $appManager) {
		$this->manager = $manager;
		$this->settingsManager = $settingsManager;
		$this->appManager = $appManager;
	}

	/**
	 * @return array{
	 *     files_sharing: array{
	 *         sharebymail: array{
	 *             enabled: bool,
	 *             send_password_by_mail: bool,
	 *             upload_files_drop: array{
	 *                 enabled: bool,
	 *             },
	 *             password: array{
	 *                 enabled: bool,
	 *                 enforced: bool,
	 *             },
	 *             expire_date: array{
	 *                 enabled: bool,
	 *                 enforced: bool,
	 *             },
	 *         }
	 *     }
	 * }|array<empty>
	 */
	public function getCapabilities(): array {
		if (!$this->appManager->isEnabledForUser('files_sharing')) {
			return [];
		}
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
