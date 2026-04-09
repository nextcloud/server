<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Command;

use OCA\DAV\CalDAV\RetentionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetentionCleanupCommand extends Command {
	public function __construct(
		private RetentionService $service,
	) {
		parent::__construct('dav:retention:clean-up');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->service->cleanUp();

		return self::SUCCESS;
	}
}
