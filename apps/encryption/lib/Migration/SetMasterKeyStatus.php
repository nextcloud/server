<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Migration;

use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Class SetPasswordColumn
 *
 * @package OCA\Files_Sharing\Migration
 */
class SetMasterKeyStatus implements IRepairStep {


	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName() {
		return 'Write default encryption module configuration to the database';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if (!$this->shouldRun()) {
			return;
		}

		// if no config for the master key is set we set it explicitly to '0' in
		// order not to break old installations because the default changed to '1'.
		$configAlreadySet = $this->config->getAppValue('encryption', 'useMasterKey', 'not-set');
		if ($configAlreadySet === 'not-set') {
			$this->config->setAppValue('encryption', 'useMasterKey', '0');
		}
	}

	protected function shouldRun() {
		$appVersion = $this->config->getAppValue('encryption', 'installed_version', '0.0.0');
		return version_compare($appVersion, '2.0.0', '<');
	}
}
