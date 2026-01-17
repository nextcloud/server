<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command;

use OCP\Snowflake\ISnowflakeDecoder;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SnowflakeDecodeId extends Base {
	public function __construct(
		private readonly ISnowflakeDecoder $decoder,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('snowflake:decode')
			->setDescription('Decode Snowflake IDs used by Nextcloud')
			->addArgument('snowflake-id', InputArgument::REQUIRED, 'Nextcloud Snowflake ID to decode');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$snowflakeId = $input->getArgument('snowflake-id');
		$data = $this->decoder->decode($snowflakeId);

		$rows = [
			['Snowflake ID', $snowflakeId],
			['Seconds', $data->getSeconds()],
			['Milliseconds', $data->getMilliseconds()],
			['Created from CLI', $data->isCli() ? 'yes' : 'no'],
			['Server ID', $data->getServerId()],
			['Sequence ID', $data->getSequenceId()],
			['Creation timestamp', $data->getCreatedAt()->format('U.v')],
			['Creation date', $data->getCreatedAt()->format('Y-m-d H:i:s.v')],
		];

		$table = new Table($output);
		$table->setRows($rows);
		$table->render();

		return Base::SUCCESS;
	}
}
