<?php

namespace OCA\UpdateNotification\Command;

use OC\App\AppManager;
use OC\Installer;
use OCA\UpdateNotification\UpdateChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Check extends Command {

	/**
	 * @var Installer $installer
	 */
	private $installer;

	/**
	 * @var AppManager $appManager
	 */
	private $appManager;

	/**
	 * @var UpdateChecker $updateChecker
	 */
	private $updateChecker;

	public function __construct(AppManager $appManager, UpdateChecker $updateChecker, Installer $installer) {
		parent::__construct();
		$this->installer = $installer;
		$this->appManager = $appManager;
		$this->updateChecker = $updateChecker;
	}

	protected function configure() {
		$this
			->setName('update:check')
			->setDescription('Check for server and app updates')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		// Server
		$r = $this->updateChecker->getUpdateState();
		if ($r['updateAvailable']) {
			$output->writeln($r['updateVersion'] . ' is available. Get more information on how to update at '. $r['updateLink'] . '.');
		}


		// Apps
		$apps = $this->appManager->getInstalledApps();
		foreach ($apps as $app) {
			$update = $this->installer->isUpdateAvailable($app);
			if ($update !== false) {
				$output->writeln('Update for ' . $app . ' to version ' . $update . ' is available.');
			}
		}

		return 0;
	}
}
