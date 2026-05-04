<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use Closure;
use OC\Core\Command\Base;
use OCP\Sharing\Exception\AShareException;
use OCP\Sharing\IManager;
use OCP\Sharing\ShareAccessContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SharingBase extends Command {
	public ShareAccessContext $accessContext;

	public function __construct(
		protected readonly IManager $manager,
	) {
		parent::__construct();
		$this->accessContext = new ShareAccessContext(force: true);
	}

	/**
	 * @param Closure():string $closure
	 * @return Base::SUCCESS|Base::FAILURE
	 */
	protected function wrapExecution(OutputInterface $output, Closure $closure): int {
		try {
			$id = $closure();
			$output->writeln(json_encode($this->manager->getShare($this->accessContext, $id)->format(), JSON_THROW_ON_ERROR));
			return Base::SUCCESS;
		} catch (AShareException $aShareException) {
			if ($output instanceof ConsoleOutputInterface) {
				$output = $output->getErrorOutput();
			}

			$output->writeln($aShareException->getMessage());
			return Base::FAILURE;
		}
	}
}
