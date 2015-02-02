<?php
/**
 * Copyright (c) 2015 Morris Jobke <hey@morrisjobke.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OC\RepairStep;
use OCP\IConfig;

/**
 * Class EnableFilesApp - enables files app if disabled
 *
 * TODO: remove this with ownCloud 8.1 - this isn't possible anymore with 8.0
 *
 * @package OC\Repair
 */
class EnableFilesApp extends BasicEmitter implements RepairStep {

	/** @var IConfig */
	protected $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Re-enable file app';
	}

	/**
	 * Enables the files app if it is disabled
	 */
	public function run() {
		if ($this->config->getAppValue('files', 'enabled', 'no') !== 'yes') {
			$this->config->setAppValue('files', 'enabled', 'yes');
			$this->emit('\OC\Repair', 'info', ['Files app was disabled - re-enabled']);
		}
	}
}
