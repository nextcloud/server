<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Memcache;

use OC\Core\Command\Base;
use OC\RedisFactory;
use OCP\ICertificateManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedisCommand extends Base {
	public function __construct(
		protected ICertificateManager $certificateManager,
		protected RedisFactory $redisFactory,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('memcache:redis:command')
			->setDescription('Send raw redis command to the configured redis server')
			->addArgument('redis-command', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The command to run');
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$command = $input->getArgument('redis-command');
		if (!$this->redisFactory->isAvailable()) {
			$output->writeln('<error>No redis server configured</error>');
			return 1;
		}
		try {
			$redis = $this->redisFactory->getInstance();
		} catch (\Exception $e) {
			$output->writeln('Failed to connect to redis: ' . $e->getMessage());
			return 1;
		}

		$redis->setOption(\Redis::OPT_REPLY_LITERAL, true);
		$result = $redis->rawCommand(...$command);
		if ($result === false) {
			$output->writeln('<error>Redis command failed</error>');
			return 1;
		}
		$output->writeln($result);
		return 0;
	}
}
