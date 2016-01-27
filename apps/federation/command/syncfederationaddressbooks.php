<?php

namespace OCA\Federation\Command;

use OCA\Federation\DbHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncFederationAddressBooks extends Command {

	/** @var \OCA\Federation\SyncFederationAddressBooks */
	private $syncService;

	/**
	 * @param \OCA\Federation\SyncFederationAddressBooks $syncService
	 */
	function __construct(\OCA\Federation\SyncFederationAddressBooks $syncService) {
		parent::__construct();

		$this->syncService = $syncService;
	}

	protected function configure() {
		$this
			->setName('federation:sync-addressbooks')
			->setDescription('Synchronizes addressbooks of all federated clouds');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$progress = new ProgressBar($output);
		$progress->start();
		$this->syncService->syncThemAll(function($url, $ex) use ($progress, $output) {
			if ($ex instanceof \Exception) {
				$output->writeln("Error while syncing $url : " . $ex->getMessage());
			} else {
				$progress->advance();
			}
		});

		$progress->finish();
		$output->writeln('');

		return 0;
	}
}
