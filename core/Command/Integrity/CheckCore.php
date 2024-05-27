<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
