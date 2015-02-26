<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files\Command;

use OC\ForbiddenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Scan extends Command {

	/**
	 * @var \OC\User\Manager $userManager
	 */
	private $userManager;

	public function __construct(\OC\User\Manager $userManager) {
		$this->userManager = $userManager;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('files:scan')
			->setDescription('rescan filesystem')
			->addArgument(
				'user_id',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'will rescan all files of the given user(s)'
			)
			->addOption(
				'path',
				'p',
				InputArgument::OPTIONAL,
				'limit rescan to this path, eg. --path="/alice/files/Music", the user_id is determined by the path and the user_id parameter and --all are ignored'
			)
			->addOption(
				'quiet',
				'q',
				InputOption::VALUE_NONE,
				'suppress output'
			)
			->addOption(
				'all',
				null,
				InputOption::VALUE_NONE,
				'will rescan all files of all known users'
			);
	}

	protected function scanFiles($user, $path, $quiet, OutputInterface $output) {
		$scanner = new \OC\Files\Utils\Scanner($user, \OC::$server->getDatabaseConnection());
		if (!$quiet) {
			$scanner->listen('\OC\Files\Utils\Scanner', 'scanFile', function ($path) use ($output) {
				$output->writeln("Scanning file   <info>$path</info>");
			});
			$scanner->listen('\OC\Files\Utils\Scanner', 'scanFolder', function ($path) use ($output) {
				$output->writeln("Scanning folder <info>$path</info>");
			});
		}
		try {
			$scanner->scan($path);
		} catch (ForbiddenException $e) {
			$output->writeln("<error>Home storage for user $user not writable</error>");
			$output->writeln("Make sure you're running the scan command only as the user the web server runs as");
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$path = $input->getOption('path');
		if ($path) {
			$path = '/'.trim($path, '/');
			list (, $user, ) = explode('/', $path, 3);
			$users = array($user);
		} else if ($input->getOption('all')) {
			$users = $this->userManager->search('');
		} else {
			$users = $input->getArgument('user_id');
		}
		$quiet = $input->getOption('quiet');


		if (count($users) === 0) {
			$output->writeln("<error>Please specify the user id to scan, \"--all\" to scan for all users or \"--path=...\"</error>");
			return;
		}

		foreach ($users as $user) {
			if (is_object($user)) {
				$user = $user->getUID();
			}
			if ($this->userManager->userExists($user)) {
				$this->scanFiles($user, $path, $quiet, $output);
			} else {
				$output->writeln("<error>Unknown user $user</error>");
			}
		}
	}
}
