<?php
/**
 * SPDX-FileCopyrightText: 2015 Christian Kampka <christian@kampka.net>
 * SPDX-License-Identifier: MIT
 */
namespace OC\Core\Command\Background;

use OCP\IAppConfig;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An abstract base class for configuring the background job mode
 * from the command line interface.
 * Subclasses will override the getMode() function to specify the mode to configure.
 */
abstract class Base extends Command {
	abstract protected function getMode(): string;

	public function __construct(
		protected IAppConfig $config,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$mode = $this->getMode();
		$this->setName("background:$mode")
			->setDescription("Use $mode to run background jobs");
	}

	/**
	 * Executing this command will set the background job mode for owncloud.
	 * The mode to set is specified by the concrete sub class by implementing the
	 * getMode() function.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mode = $this->getMode();
		$this->config->setValueString('core', 'backgroundjobs_mode', $mode);
		$output->writeln("Set mode for background jobs to '$mode'");
		return 0;
	}
}
