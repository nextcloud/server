<?php
/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\App;

use OCP\App\IAppManager;
use Stecman\Component\Symfony\Console\BashCompletion\Completion\CompletionAwareInterface;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Command implements CompletionAwareInterface {
	protected int $exitCode = 0;

	public function __construct(
		protected IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('app:disable')
			->setDescription('disable an app')
			->addArgument(
				'app-id',
				InputArgument::REQUIRED | InputArgument::IS_ARRAY,
				'disable the specified app'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$appIds = $input->getArgument('app-id');

		foreach ($appIds as $appId) {
			$this->disableApp($appId, $output);
		}

		return $this->exitCode;
	}

	private function disableApp(string $appId, OutputInterface $output): void {
		if ($this->appManager->isInstalled($appId) === false) {
			$output->writeln('No such app enabled: ' . $appId);
			return;
		}

		try {
			$this->appManager->disableApp($appId);
			$appVersion = $this->appManager->getAppVersion($appId);
			$output->writeln($appId . ' ' . $appVersion . ' disabled');
		} catch (\Exception $e) {
			$output->writeln($e->getMessage());
			$this->exitCode = 2;
		}
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeOptionValues($optionName, CompletionContext $context): array {
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		if ($argumentName === 'app-id') {
			return array_diff(\OC_App::getEnabledApps(true, true), $this->appManager->getAlwaysEnabledApps());
		}
		return [];
	}
}
