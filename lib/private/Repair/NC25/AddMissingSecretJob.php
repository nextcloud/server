<?php

declare(strict_types=1);

/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
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
namespace OC\Repair\NC25;

use OCP\HintException;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Security\ISecureRandom;

class AddMissingSecretJob implements IRepairStep {
	private IConfig $config;
	private ISecureRandom $random;

	public function __construct(IConfig $config, ISecureRandom $random) {
		$this->config = $config;
		$this->random = $random;
	}

	public function getName(): string {
		return 'Add possibly missing system config';
	}

	public function run(IOutput $output): void {
		$passwordSalt = $this->config->getSystemValueString('passwordsalt', '');
		if ($passwordSalt === '') {
			try {
				$this->config->setSystemValue('passwordsalt', $this->random->generate(30));
			} catch (HintException $e) {
				$output->warning("passwordsalt is missing from your config.php and your config.php is read only. Please fix it manually.");
			}
		}

		$secret = $this->config->getSystemValueString('secret', '');
		if ($secret === '') {
			try {
				$this->config->setSystemValue('secret', $this->random->generate(48));
			} catch (HintException $e) {
				$output->warning("secret is missing from your config.php and your config.php is read only. Please fix it manually.");
			}
		}
	}
}
