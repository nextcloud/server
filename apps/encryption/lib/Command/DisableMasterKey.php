<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Encryption\Command;

use OCA\Encryption\Util;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DisableMasterKey extends Command {
	public function __construct(
		protected Util $util,
		protected IConfig $config,
		protected QuestionHelper $questionHelper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('encryption:disable-master-key')
			->setDescription('Disable the master key and use per-user keys instead. Only available for fresh installations with no existing encrypted data! There is no way to enable it again.');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$isMasterKeyEnabled = $this->util->isMasterKeyEnabled();

		if (!$isMasterKeyEnabled) {
			$output->writeln('Master key already disabled');
			return self::SUCCESS;
		}

		$question = new ConfirmationQuestion(
			'Warning: Only perform this operation for a fresh installations with no existing encrypted data! '
			. 'There is no way to enable the master key again. '
			. 'We strongly recommend to keep the master key, it provides significant performance improvements '
			. 'and is easier to handle for both, users and administrators. '
			. 'Do you really want to switch to per-user keys? (y/n) ', false);

		if ($this->questionHelper->ask($input, $output, $question)) {
			$this->config->setAppValue('encryption', 'useMasterKey', '0');
			$output->writeln('Master key successfully disabled.');
			return self::SUCCESS;
		}

		$output->writeln('aborted.');
		return self::FAILURE;
	}
}
