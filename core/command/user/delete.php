<?php
/**
 * Copyright (c) 2014 Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Core\Command\User;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Delete extends Command {
	/** @var \OC\User\Manager */
	protected $userManager;

	/**
	 * @param \OC\User\Manager $userManager
	 */
	public function __construct(\OC\User\Manager $userManager) {
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('user:delete')
			->setDescription('deletes the specified user')
			->addArgument(
				'uid',
				InputArgument::REQUIRED,
				'the username'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$wasSuccessful = $this->userManager->get($input->getArgument('uid'))->delete();
		if($wasSuccessful === true) {
			$output->writeln('The specified user was deleted');
			return;
		}
		$output->writeln('<error>The specified could not be deleted. Please check the logs.</error>');
	}
}
