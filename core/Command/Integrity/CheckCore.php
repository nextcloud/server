<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Carla Schroder <carla@owncloud.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CheckCore
 *
 * @package OC\Core\Command\Integrity
 */
class CheckCore extends Base {
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
			->setName('integrity:check-core')
			->setDescription('Check integrity of core code using a signature.');
	}

	/**
	 * {@inheritdoc }
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		if (!$this->checker->isCodeCheckEnforced()) {
			$output->writeln('<comment>integrity:check-core can not be used on git checkouts</comment>');
			return 2;
		}

		$result = $this->checker->verifyCoreSignature();
		$this->writeArrayInOutputFormat($input, $output, $result);
		if (count($result) > 0) {
			$output->writeln('<error>' . count($result) . ' errors found</error>', OutputInterface::VERBOSITY_VERBOSE);
			return 1;
		}
		$output->writeln('<info>No errors found</info>', OutputInterface::VERBOSITY_VERBOSE);
		return 0;
	}
}
