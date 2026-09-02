<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\Command;

use Closure;
use Exception;
use NCU\Sharing\Exception\AShareException;
use NCU\Sharing\ISharingManager;
use NCU\Sharing\ISharingRegistry;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use OC\Core\Command\Base;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class SharingBase extends Base {
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

	#[\Override]
	public function configure(): void {
		$this
			->addOption('actor', null, InputOption::VALUE_REQUIRED, 'User ID to use as the actor for any share modification');
		parent::configure();
	}

	protected function applyActor(InputInterface $input): void {
		/** @var ?string $actorId */
		$actorId = $input->getOption('actor');

		if ($actorId !== null) {
			$actor = $this->userManager->get($actorId);
			$this->accessContext = new ShareAccessContext(currentUser: $actor, overrideChecks: true);
		}
	}

	/**
	 * @param Closure():Share $closure
	 */
	protected function wrapExecution(InputInterface $input, OutputInterface $output, Closure $closure): int {
		$this->applyActor($input);

		try {
			try {
				$this->dbConnection->beginTransaction();

				$share = $closure();
				$this->dbConnection->commit();

				$data = $share->format($this->registry, $this->l10nFactory, $this->urlGenerator, $this->userManager, $this->accessContext);
				$this->writeArrayInOutputFormat($input, $output, $data);

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
