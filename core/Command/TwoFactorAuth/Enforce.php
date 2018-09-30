<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Core\Command\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Enforce extends Command {

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	public function __construct(MandatoryTwoFactor $mandatoryTwoFactor) {
		parent::__construct();

		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
	}

	protected function configure() {
		$this->setName('twofactorauth:enforce');
		$this->setDescription('Enabled/disable enforced two-factor authentication');
		$this->addOption(
			'on',
			null,
			InputOption::VALUE_NONE,
			'enforce two-factor authentication'
		);
		$this->addOption(
			'off',
			null,
			InputOption::VALUE_NONE,
			'don\'t enforce two-factor authenticaton'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		if ($input->getOption('on')) {
			$this->mandatoryTwoFactor->setEnforced(true);
		} elseif ($input->getOption('off')) {
			$this->mandatoryTwoFactor->setEnforced(false);
		}

		if ($this->mandatoryTwoFactor->isEnforced()) {
			$this->writeEnforced($output);
		} else {
			$this->writeNotEnforced($output);
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function writeEnforced(OutputInterface $output) {
		$output->writeln('Two-factor authentication is enforced for all users');
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function writeNotEnforced(OutputInterface $output) {
		$output->writeln('Two-factor authentication is not enforced');
	}

}
