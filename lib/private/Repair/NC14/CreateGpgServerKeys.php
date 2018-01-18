<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OC\Repair\NC14;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IGpg;

class CreateGpgServerKeys implements IRepairStep {
	/** @var IConfig */
	private $config;

	/** @var ILogger */
	private $logger;

	/** @var IGpg */
	private $gpg;

	/**
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param IGpg $gpg
	 */
	public function __construct(IConfig $config,
								ILogger $logger,
								IGpg $gpg) {
		$this->logger = $logger;
		$this->config = $config;
		$this->gpg = $gpg;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'Create server gpg key pair';
	}

	/**
	 * {@inheritdoc}
	 */
	public function run(IOutput $output) {
		$fingerprint = $this->gpg->generateKey();
		$this->logger->info("Created server gpg key pair ".$fingerprint, ['app' => 'core']);
	}
}
