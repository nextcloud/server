<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command;

use OC\Snowflake\Decoder;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SnowflakeDecodeId extends Base {
	protected function configure(): void {
		parent::configure();

		$this
			->setName('decode-snowflake')
			->setDescription('Decode Snowflake IDs used by Nextcloud')
			->addArgument('snowflake-id', InputArgument::REQUIRED, 'Nextcloud Snowflake ID to decode');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$snowflakeId = $input->getArgument('snowflake-id');
		$data = (new Decoder)->decode($snowflakeId);

		$rows = [
			['Snowflake ID', $snowflakeId],
			['Seconds', $data['seconds']],
			['Milliseconds', $data['milliseconds']],
			['Created from CLI', $data['isCli'] ? 'yes' : 'no'],
			['Server ID', $data['serverId']],
			['Sequence ID', $data['sequenceId']],
			['Creation timestamp', $data['createdAt']->format('U.v')],
			['Creation date', $data['createdAt']->format('Y-m-d H:i:s.v')],
		];

		$table = new Table($output);
		$table->setRows($rows);
		$table->render();

		return Base::SUCCESS;
	}
}
