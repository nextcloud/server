<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Async;

use OC\Async\AProcessWrapper;
use OC\Async\AsyncManager;
use OC\Async\AsyncProcess;
use OC\Async\Enum\ProcessActivity;
use OC\Async\Enum\ProcessExecutionTime;
use OC\Async\Wrappers\CliProcessWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Setup extends Command {

	public function __construct(
		private AsyncManager $asyncManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('async:setup')
			 ->addOption('reset', '', InputOption::VALUE_NONE, 'reset data')
			 ->addOption('loopback', '', InputOption::VALUE_REQUIRED, 'set loopback address')
			 ->addOption('emul-session', '', InputOption::VALUE_OPTIONAL, 'create x sessions', '0')
			 ->addOption('emul-process', '', InputOption::VALUE_OPTIONAL, 'create x processes', '0')
			 ->addOption('test', '', InputOption::VALUE_NONE, 'run a list of specific test', '0')
			 ->setDescription('setup ');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('reset')) {
			$this->asyncManager->dropAll();

			return 0;
		}

		CliProcessWrapper::initStyle($output);
		AsyncProcess::setWrapper(new CliProcessWrapper($output));

		$session = (int)($input->getOption('session') ?? 0);
		$proc = (int)($input->getOption('proc') ?? 0);
		if ($session > 0 || $proc > 0) {
			$this->createRandomProcess($output, $session, $proc);

			return 0;
		}

		foreach ($this->asyncManager->listStandBySessions() as $session) {
			$this->asyncManager->forkSession($session);
		}

		// we wait until all child process are done
		/** @noinspection PhpStatementHasEmptyBodyInspection */
		while (pcntl_waitpid(0, $status) != -1) {
		}

		return 0;
	}

	private function createRandomProcess(OutputInterface $output, int $session, int $processes) {
		$session = ($session > 0) ? $session : 1;
		$processes = ($processes > 0) ? $processes : 1;

		for ($i = 0; $i < $session; $i++) {
			for ($j = 0; $j < $processes; $j++) {
				$output->writeln(
					'- creating process ' .
					AsyncProcess::exec(
						function (AProcessWrapper $wrapper, $data, int $j): array {
							if ($j > 0) {
								$wrapper->activity(
									ProcessActivity::NOTICE,
									'>> Result from first process of the session: ' . json_encode($wrapper->getSessionInterface()->byId('id_id_0')?->getResult())
								);
							}
							sleep(random_int(2, 8));
							$wrapper->activity(ProcessActivity::NOTICE, 'this is a test ' . json_encode($data));



							sleep(random_int(2, 12));
							$wrapper->activity(ProcessActivity::NOTICE, 'another long process just end');

							sleep(random_int(1, 5));
							// end.

							return ['my_result_is' => random_int(10000, 999999)];
						},
						['ouila' => random_int(1, 50)],
						$j
					)->name('this is test ' . $i . $j)

					 ->id('id_id_' . $j)
								->require('123')
								->dataset([['123'], ['123'], ['444']])
								->getToken()
				);


			}

			$token = AsyncProcess::async(ProcessExecutionTime::LATER);

			$output->writeln('session prep: ' . $token);
			$output->writeln('');
		}
	}
}
