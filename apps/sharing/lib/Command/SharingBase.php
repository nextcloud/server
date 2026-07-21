<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use Closure;
use Exception;
use OC\Core\Command\Base;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Sharing\Exception\AShareException;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\ShareAccessContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SharingBase extends Command {
	public ShareAccessContext $accessContext;

	public function __construct(
		protected readonly ISharingManager $manager,
		protected readonly ISharingRegistry $registry,
		protected readonly IFactory $l10nFactory,
		protected readonly IURLGenerator $urlGenerator,
		protected readonly IUserManager $userManager,
		protected readonly IDBConnection $dbConnection,
	) {
		parent::__construct();
		$this->accessContext = new ShareAccessContext(overrideChecks: true);
	}

	/**
	 * @param Closure():string $closure
	 */
	protected function wrapExecution(OutputInterface $output, Closure $closure): int {

		try {
			try {
				$this->dbConnection->beginTransaction();

				$id = $closure();
				$share = $this->manager->getShare($this->accessContext, $id);
				$this->dbConnection->commit();
				$output->writeln(json_encode($share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager), JSON_THROW_ON_ERROR));
				return Base::SUCCESS;
			} catch (Exception $exception) {
				$this->dbConnection->rollBack();
				throw $exception;
			}
		} catch (AShareException $aShareException) {
			if ($output instanceof ConsoleOutputInterface) {
				$output = $output->getErrorOutput();
			}

			$output->writeln($aShareException->getHint());
			return Base::FAILURE;
		}
	}
}
