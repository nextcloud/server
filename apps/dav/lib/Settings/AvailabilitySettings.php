<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Settings;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\Db\AbsenceMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;
use OCP\User\IAvailabilityCoordinator;
use Psr\Log\LoggerInterface;

class AvailabilitySettings implements ISettings {
	public function __construct(
		protected IConfig $config,
		protected IInitialState $initialState,
		protected ?string $userId,
		private LoggerInterface $logger,
		private IAvailabilityCoordinator $coordinator,
		private AbsenceMapper $absenceMapper,
	) {
	}

	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState(
			'user_status_automation',
			$this->config->getUserValue(
				$this->userId,
				'dav',
				'user_status_automation',
				'no'
			)
		);
		$hideAbsenceSettings = !$this->coordinator->isEnabled();
		$this->initialState->provideInitialState('hide_absence_settings', $hideAbsenceSettings);
		if (!$hideAbsenceSettings) {
			try {
				$absence = $this->absenceMapper->findByUserId($this->userId);
				$this->initialState->provideInitialState('absence', $absence);
			} catch (DoesNotExistException) {
				// The user has not yet set up an absence period.
				// Logging this error is not necessary.
			} catch (\OCP\DB\Exception $e) {
				$this->logger->error("Could not find absence data for user $this->userId: " . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}

		return new TemplateResponse(Application::APP_ID, 'settings-personal-availability');
	}

	public function getSection(): string {
		return 'availability';
	}

	public function getPriority(): int {
		return 10;
	}
}
