<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\Core\Command\MessageQueue;

use OC\Core\Command\Base;
use OC\MessageQueue\Consumer;
use OCP\MessageQueue\Queue;
use OCP\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Consume extends Base {
	public function __construct(
		private readonly Consumer $consumer,
	) {
		parent::__construct();
	}

	#[\Override]
	protected function configure(): void {
		$this
			->setName('message-queue:consume')
			->setDescription('Run a worker consuming messages from the message queue')
			->addArgument(
				'queues',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'Queues to consume, in order of priority (' . implode(', ', array_map(static fn (Queue $queue) => $queue->value, Queue::cases())) . ')',
			)
			->addOption('time-limit', 't', InputOption::VALUE_REQUIRED, 'Stop after this many seconds')
			->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Stop after handling this many messages')
			->addOption('memory-limit', 'm', InputOption::VALUE_REQUIRED, 'Stop when the memory usage exceeds this limit, e.g. 512M')
			->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to wait before polling again when all queues are empty', '1')
			->addOption('stop-when-empty', null, InputOption::VALUE_NONE, 'Stop as soon as all queues are empty');
	}

	#[\Override]
	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var list<string> $names */
		$names = $input->getArgument('queues');
		$queues = [];
		foreach ($names as $name) {
			$queue = Queue::tryFrom($name);
			if ($queue === null) {
				$output->writeln('<error>Unknown queue ' . $name . '</error>');
				return self::FAILURE;
			}

			$queues[] = $queue;
		}

		/** @var mixed $memoryLimit */
		$memoryLimit = $input->getOption('memory-limit');
		$this->consumer->consume(
			queues: $queues ?: Queue::cases(),
			timeLimit: $this->getIntOption($input, 'time-limit'),
			messageLimit: $this->getIntOption($input, 'limit'),
			memoryLimit: is_string($memoryLimit) ? (int)Util::computerFileSize($memoryLimit) : null,
			stopWhenEmpty: (bool)$input->getOption('stop-when-empty'),
			sleep: (int)$input->getOption('sleep'),
			output: $output->isVerbose() ? static fn (string $message) => $output->writeln($message) : null,
		);

		return self::SUCCESS;
	}

	#[\Override]
	public function cancelOperation(): void {
		parent::cancelOperation();
		$this->consumer->stop();
	}

	private function getIntOption(InputInterface $input, string $name): ?int {
		/** @var mixed $value */
		$value = $input->getOption($name);
		return is_string($value) ? (int)$value : null;
	}
}
