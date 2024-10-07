<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Command;

use OC\Core\Command\Base;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\Http;
use OCP\IUserManager;
use OCP\IUserSession;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Delete extends Base {
	public function __construct(
		protected GlobalStoragesService $globalService,
		protected UserStoragesService $userService,
		protected IUserSession $userSession,
		protected IUserManager $userManager,
		protected QuestionHelper $questionHelper,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files_external:delete')
			->setDescription('Delete an external mount')
			->addArgument(
				'mount_id',
				InputArgument::REQUIRED,
				'The id of the mount to edit'
			)->addOption(
				'yes',
				'y',
				InputOption::VALUE_NONE,
				'Skip confirmation'
			);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mountId = $input->getArgument('mount_id');
		try {
			$mount = $this->globalService->getStorage($mountId);
		} catch (NotFoundException $e) {
			$output->writeln('<error>Mount with id "' . $mountId . ' not found, check "occ files_external:list" to get available mounts"</error>');
			return Http::STATUS_NOT_FOUND;
		}

		$noConfirm = $input->getOption('yes');

		if (!$noConfirm) {
			$listCommand = new ListCommand($this->globalService, $this->userService, $this->userSession, $this->userManager);
			$listInput = new ArrayInput([], $listCommand->getDefinition());
			$listInput->setOption('output', $input->getOption('output'));
			$listCommand->listMounts(null, [$mount], $listInput, $output);

			/** @var QuestionHelper $questionHelper */
			$questionHelper = $this->getHelper('question');
			$question = new ConfirmationQuestion('Delete this mount? [y/N] ', false);

			if (!$questionHelper->ask($input, $output, $question)) {
				return self::FAILURE;
			}
		}

		$this->globalService->removeStorage($mountId);
		return self::SUCCESS;
	}
}
