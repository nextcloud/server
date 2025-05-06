<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Async;

use OC\Async\ABlockWrapper;
use OC\Async\AsyncManager;
use OC\Async\Exceptions\LoopbackEndpointException;
use OC\Async\ForkManager;
use OC\Config\Lexicon\CoreConfigLexicon;
use OCP\Async\Enum\BlockActivity;
use OCP\Async\Enum\ProcessExecutionTime;
use OCP\Async\IAsyncProcess;
use OCP\IAppConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Setup extends Command {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IAsyncProcess $asyncProcess,
		private readonly AsyncManager $asyncManager,
		private readonly ForkManager $forkManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		parent::configure();
		$this->setName('async:setup')
			 ->addOption('reset', '', InputOption::VALUE_NONE, 'reset all data related to the AsyncProcess feature')
			 ->addOption('drop', '', InputOption::VALUE_NONE, 'drop processes data')
			 ->addOption('loopback', '', InputOption::VALUE_REQUIRED, 'set loopback address')
			 ->addOption('discover', '', InputOption::VALUE_NONE, 'initiate the search for a possible loopback address')
			 ->addOption('yes', '', InputOption::VALUE_NONE, 'answer yes to any confirmation box')
			 ->addOption('exception-if-no-config', '', InputOption::VALUE_NONE, 'return an exception if existing configuration cannot be used')
			 ->addOption('mock-session', '', InputOption::VALUE_OPTIONAL, 'create n sessions', '0')
			 ->addOption('mock-block', '', InputOption::VALUE_OPTIONAL, 'create n blocks', '0')
			 ->addOption('fail-process', '', InputOption::VALUE_REQUIRED, 'create fail process', '')
			 ->setDescription('setup');
	}

	/**
	 * @throws LoopbackEndpointException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$reset = $input->getOption('reset');
		$drop = $input->getOption('drop');
		if ($reset || $drop) {
			if (!$input->getOption('yes')) {
				$question = new ConfirmationQuestion(
					($reset) ? '<comment>Do you want to reset all data and configuration related to AsyncProcess ?</comment> (y/N) '
						: '<comment>Do you want to drop all data related to AsyncProcess ?</comment> (y/N) ',
					false,
					'/^(y|Y)/i'
				);

				/** @var QuestionHelper $helper */
				$helper = $this->getHelper('question');
				if (!$helper->ask($input, $output, $question)) {
					$output->writeln('aborted.');

					return 0;
				}
			}

			$this->asyncManager->dropAllBlocks();
			if ($reset) {
				$this->asyncManager->resetConfig();
			}

			$output->writeln('done.');

			return 0;
		}

		$failureType = $input->getOption('fail-process');
		if ($failureType !== '') {
			try {
				match ($failureType) {
					'static-required' => $this->createFaultyProcessStaticRequired(),
					'static-blocker' => $this->createFaultyProcessStaticBlocker(),
					'dynamic-required' => $this->createFaultyProcessDynamicRequired(),
					'dynamic-blocker' => $this->createFaultyProcessDynamicBlocker(),
					'auto-required' => $this->createFaultyProcessAutoRequired(),
					'auto-blocker' => $this->createFaultyProcessAutoBlocker(),
				};
			} catch (\UnhandledMatchError) {
				$output->writeln(
					'list of fail: static-required, static-blocker, dynamic-blocker, dynamic-required, auto-blocker, auto-required'
				);
			}

			return 0;
		}

		$session = (int)($input->getOption('mock-session') ?? 0);
		$proc = (int)($input->getOption('mock-block') ?? 0);
		if ($session > 0 || $proc > 0) {
			$this->createRandomProcess($output, $session, $proc);

			$this->forkManager->waitChildProcess();

			return 0;
		}

		if ($input->getOption('discover')) {
			$output->writeln('<info>Searching for loopback address</info>');
			$found = $this->forkManager->discoverLoopbackEndpoint($output);
			$output->writeln('found a working loopback address: <info>' . $found . '</info>');
			$this->confirmSave($input, $output, $found);

			return 0;
		}

		$inputLoopback = $input->getOption('loopback');
		if ($inputLoopback) {
			$this->parseAddress($inputLoopback);
			$output->write('- testing <comment>' . $inputLoopback . '</comment>... ');

			$reason = '';
			if (!$this->forkManager->testLoopbackInstance($inputLoopback, $reason)) {
				$output->writeln('<error>' . $reason . '</error>');

				return 0;
			}

			$output->writeln('<info>ok</info>');
			$this->confirmSave($input, $output, $inputLoopback);

			return 0;
		}

		try {
			$currentLoopback = $this->forkManager->getLoopbackInstance();
			$output->writeln('Current loopback instance: <info>' . $currentLoopback . '</info>');
			$output->write('Testing async process using loopback endpoint... ');
			$reason = '';
			if (!$this->forkManager->testLoopbackInstance($currentLoopback, $reason)) {
				$output->writeln('<error>' . $reason . '</error>');
			} else {
				$output->writeln('<info>ok</info>');
			}

			return 0;
		} catch (LoopbackEndpointException $e) {
			if ($input->getOption('exception-if-no-config')) {
				throw $e;
			}
		}

		$output->writeln('<info>Notes:</info>');
		$output->writeln('no loopback instance currently set.');
		$output->writeln('use --loopback <address> to manually configure a loopback instance');
		$output->writeln('or use --discover for an automated process');

		return 0;
	}

	private function confirmSave(InputInterface $input, OutputInterface $output, string $instance): void {
		if (!$input->getOption('yes')) {
			$output->writeln('');
			$question = new ConfirmationQuestion(
				'<comment>Do you want to save loopback address \'' . $instance . '\' ?</comment> (y/N) ',
				false,
				'/^(y|Y)/i'
			);

			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			if (!$helper->ask($input, $output, $question)) {
				$output->writeln('aborted.');

				return;
			}
		}

		$this->appConfig->setValueString('core', CoreConfigLexicon::ASYNC_LOOPBACK_ADDRESS, $instance);
	}

	/**
	 * confirm format of typed address
	 */
	private function parseAddress(string $test): void {
		$scheme = parse_url($test, PHP_URL_SCHEME);
		$cloudId = parse_url($test, PHP_URL_HOST);
		if (is_bool($scheme) || is_bool($cloudId) || is_null($scheme) || is_null($cloudId)) {
			throw new LoopbackEndpointException('format must be http[s]://domain.name[:post][/path]');
		}
	}

	/**
	 * create a list of fake/mockup process
	 *
	 * @param int $session number of session to generate
	 * @param int $processes number of process per session to generate
	 */
	private function createRandomProcess(OutputInterface $output, int $session, int $processes): void {
		$session = ($session > 0) ? $session : 1;
		$processes = ($processes > 0) ? $processes : 1;

		for ($i = 0; $i < $session; $i++) {
			for ($j = 0; $j < $processes; $j++) {
				$process = $this->asyncProcess->exec(
					function (ABlockWrapper $wrapper, int $data, int $j): array {
						if ($j > 0) {
							$wrapper->activity(
								BlockActivity::NOTICE, 'result from first process of the session: '
													   . $wrapper->getSessionInterface()
																   ->byId('mock_process_0')
																   ?->getResult()['result']
							);
						}
						sleep(random_int(2, 8));
						$wrapper->activity(
							BlockActivity::NOTICE, 'mocked process is now over with data=' . $data
						);

						return ['result' => $data];
					},
					random_int(1, 5000),
					$j
				)->id('mock_process_' . $j)->blocker();
				$output->writeln('  > creating process <info>' . $process->getToken() . '</info>');
			}

			$token = $this->asyncProcess->async(ProcessExecutionTime::LATER);

			$output->writeln('- session <info>' . $token . '</info>');
			$output->writeln('');
		}
	}

	private function createFaultyProcessStaticRequired(): void {
		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper, int $n): array {
				$wrapper->activity(
					BlockActivity::NOTICE, '(1) this process will crash in ' . $n . ' seconds'
				);
				sleep($n);
				throw new \Exception('crash');
			},
			random_int(2, 8)
		)->id('mock_process_1');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): void {
				$wrapper->activity(BlockActivity::ERROR, '(2) this process should NOT run');
			},
		)->id('mock_process_2')->require('mock_process_1');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): array {
				$wrapper->activity(BlockActivity::NOTICE, '(3) this process should run!');

				return ['ok'];
			},
		)->id('mock_process_3');

		$this->asyncProcess->async(ProcessExecutionTime::LATER);
	}

	private function createFaultyProcessStaticBlocker(): void {
		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper, int $n): array {
				$wrapper->activity(
					BlockActivity::NOTICE, '(1) this process will crash in ' . $n . ' seconds'
				);
				sleep($n);
				throw new \Exception('crash');
			},
			random_int(2, 8)
		)->id('mock_process_1')->blocker();

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): void {
				$wrapper->activity(BlockActivity::ERROR, '(2) this process should NOT run');
			},
		)->id('mock_process_2');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): array {
				$wrapper->activity(BlockActivity::ERROR, '(3) this process should NOT run');

				return ['ok'];
			},
		)->id('mock_process_3');

		$this->asyncProcess->async(ProcessExecutionTime::LATER);
	}


	private function createFaultyProcessDynamicRequired(): void {
		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper, int $n): array {
				if ($wrapper->getReplayCount() > 1) {
					$wrapper->activity(BlockActivity::NOTICE, '(1) this process will not crash anymore');
					return ['dynamic-required'];
				}

				$wrapper->activity(BlockActivity::NOTICE, '(1) this process will crash in ' . $n . ' seconds');
				sleep($n);
				throw new \Exception('crash');
			},
			random_int(2, 8)
		)->id('mock_process_1');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): void {
				$wrapper->activity(BlockActivity::ERROR, '(2) this process should only run after few replay from MOCK_PROCESS_1');
			},
		)->id('mock_process_2')->require('mock_process_1');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): array {
				$wrapper->activity(BlockActivity::NOTICE, '(3) this process should run!');

				return ['ok'];
			},
		)->id('mock_process_3');

		$this->asyncProcess->async(ProcessExecutionTime::LATER);
	}


	private function createFaultyProcessDynamicBlocker(): void {
		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper, int $n): array {
				if ($wrapper->getReplayCount() > 1) {
					$wrapper->activity(BlockActivity::NOTICE, '(1) this process will not crash anymore');
					return ['dynamic-blocker'];
				}

				$wrapper->activity(BlockActivity::NOTICE, '(1) this process will crash in ' . $n . ' seconds');
				sleep($n);
				throw new \Exception('crash');
			},
			random_int(2, 8)
		)->id('mock_process_1')->blocker();

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): void {
				$wrapper->activity(BlockActivity::ERROR, '(2) this process should only run after few replay from MOCK_PROCESS_1');
			},
		)->id('mock_process_2');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): array {
				$wrapper->activity(BlockActivity::ERROR, '(3) this process should only run after few replay from MOCK_PROCESS_1');

				return ['ok'];
			},
		)->id('mock_process_3');

		$this->asyncProcess->async(ProcessExecutionTime::LATER);
	}



	private function createFaultyProcessAutoRequired(): void {
		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper, int $n): array {
				if ($wrapper->getReplayCount() > 1) {
					$wrapper->activity(BlockActivity::NOTICE, '(1) this process will not crash anymore');
					return ['dynamic-required'];
				}

				$wrapper->activity(BlockActivity::NOTICE, '(1) this process will crash in ' . $n . ' seconds');
				sleep($n);
				throw new \Exception('crash');
			},
			random_int(2, 8)
		)->id('mock_process_1')->replayable();

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): void {
				$wrapper->activity(BlockActivity::ERROR, '(2) this process should only run after few replay from MOCK_PROCESS_1');
			},
		)->id('mock_process_2')->require('mock_process_1');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): array {
				$wrapper->activity(BlockActivity::NOTICE, '(3) this process should run!');

				return ['ok'];
			},
		)->id('mock_process_3');

		$this->asyncProcess->async(ProcessExecutionTime::LATER);
	}


	private function createFaultyProcessAutoBlocker(): void {
		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper, int $n): array {
				if ($wrapper->getReplayCount() > 1) {
					$wrapper->activity(BlockActivity::NOTICE, '(1) this process will not crash anymore');
					return ['dynamic-blocker'];
				}

				$wrapper->activity(BlockActivity::NOTICE, '(1) this process will crash in ' . $n . ' seconds');
				sleep($n);
				throw new \Exception('crash');
			},
			random_int(2, 8)
		)->id('mock_process_1')->blocker()->replayable();

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): void {
				$wrapper->activity(BlockActivity::ERROR, '(2) this process should only run after few replay from MOCK_PROCESS_1');
			},
		)->id('mock_process_2');

		$this->asyncProcess->exec(
			function (ABlockWrapper $wrapper): array {
				$wrapper->activity(BlockActivity::ERROR, '(3) this process should only run after few replay from MOCK_PROCESS_1');

				return ['ok'];
			},
		)->id('mock_process_3');

		$this->asyncProcess->async(ProcessExecutionTime::LATER);
	}

}
