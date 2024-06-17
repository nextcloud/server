<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Command;

use OCA\Encryption\Util;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class EnableMasterKey extends Command {
	public function __construct(
		protected Util $util,
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('encryption:enable-master-key')
			->setDescription('Enable the master key. Only available for fresh installations with no existing encrypted data! There is also no way to disable it again.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$isAlreadyEnabled = $this->util->isMasterKeyEnabled();

		if ($isAlreadyEnabled) {
			$output->writeln('Master key already enabled');
			return self::SUCCESS;
		}

		$question = new ConfirmationQuestion(
			'Warning: Only available for fresh installations with no existing encrypted data! '
			. 'There is also no way to disable it again. Do you want to continue? (y/n) ', false);

		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->config->setAppValue('encryption', 'useMasterKey', '1');
			$output->writeln('Master key successfully enabled.');
			return self::SUCCESS;
		}

		$output->writeln('aborted.');
		return self::FAILURE;
	}
}
