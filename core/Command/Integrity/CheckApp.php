<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Carla Schroder <carla@owncloud.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\Integrity;

use OC\Core\Command\Base;
use OC\IntegrityCheck\Checker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckApp
 *
 * @package OC\Core\Command\Integrity
 */
class CheckApp extends Base {
	public function __construct(
		private Checker $checker,
	) {
		parent::__construct();
	}

	/**
	 * {@inheritdoc }
	 */
	protected function configure() {
		parent::configure();
		$this
			->setName('integrity:check-app')
			->setDescription('Check integrity of an app using a signature.')
			->addArgument('appid', InputArgument::REQUIRED, 'Application to check')
			->addOption('path', null, InputOption::VALUE_OPTIONAL, 'Path to application. If none is given it will be guessed.');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appid = $input->getArgument('appid');
		$path = (string)$input->getOption('path');
		$result = $this->checker->verifyAppSignature($appid, $path, true);
		$this->writeArrayInOutputFormat($input, $output, $result);
		if (count($result) > 0) {
			$output->writeln('<error>' . count($result) . ' errors found</error>', OutputInterface::VERBOSITY_VERBOSE);
			return 1;
		}
		$output->writeln('<info>No errors found</info>', OutputInterface::VERBOSITY_VERBOSE);
		return 0;
	}
}
