<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 * @author Adam Blakey <adam@blakey.family>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\App;

use OC\Core\Command\Base;
use OCP\App\IAppManager;
use Stecman\Component\Symfony\Console\BashCompletion\CompletionContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListApps extends Base {
	public function __construct(
		protected IAppManager $manager,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();

		$this
			->setName('app:list')
			->setDescription('List all available apps')
			->addOption(
				'shipped',
				null,
				InputOption::VALUE_REQUIRED,
				'true - limit to shipped apps only, false - limit to non-shipped apps only'
			)
			->addOption(
				'enabled',
				null,
				InputOption::VALUE_NONE,
				'shows only enabled apps'
			)
			->addOption(
				'disabled',
				null,
				InputOption::VALUE_NONE,
				'shows only disabled apps'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($input->getOption('shipped') === 'true' || $input->getOption('shipped') === 'false') {
			$shippedFilter = $input->getOption('shipped') === 'true';
		} else {
			$shippedFilter = null;
		}

		$showEnabledApps = $input->getOption('enabled') || !$input->getOption('disabled');
		$showDisabledApps = $input->getOption('disabled') || !$input->getOption('enabled');

		$apps = \OC_App::getAllApps();
		$enabledApps = $disabledApps = [];
		$versions = \OC_App::getAppVersions();

		//sort enabled apps above disabled apps
		foreach ($apps as $app) {
			if ($shippedFilter !== null && $this->manager->isShipped($app) !== $shippedFilter) {
				continue;
			}
			if ($this->manager->isInstalled($app)) {
				$enabledApps[] = $app;
			} else {
				$disabledApps[] = $app;
			}
		}

		$apps = [];

		if ($showEnabledApps) {
			$apps['enabled'] = [];

			sort($enabledApps);
			foreach ($enabledApps as $app) {
				$apps['enabled'][$app] = $versions[$app] ?? true;
			}
		}

		if ($showDisabledApps) {
			$apps['disabled'] = [];

			sort($disabledApps);
			foreach ($disabledApps as $app) {
				$apps['disabled'][$app] = $this->manager->getAppVersion($app) . (isset($versions[$app]) ? ' (installed ' . $versions[$app] . ')' : '');
			}
		}

		$this->writeAppList($input, $output, $apps);
		return 0;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @param array $items
	 */
	protected function writeAppList(InputInterface $input, OutputInterface $output, $items): void {
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_PLAIN:
				if (isset($items['enabled'])) {
					$output->writeln('Enabled:');
					parent::writeArrayInOutputFormat($input, $output, $items['enabled']);
				}

				if (isset($items['disabled'])) {
					$output->writeln('Disabled:');
					parent::writeArrayInOutputFormat($input, $output, $items['disabled']);
				}
				break;

			default:
				parent::writeArrayInOutputFormat($input, $output, $items);
				break;
		}
	}

	/**
	 * @param string $optionName
	 * @param CompletionContext $context
	 * @return array
	 */
	public function completeOptionValues($optionName, CompletionContext $context): array {
		if ($optionName === 'shipped') {
			return ['true', 'false'];
		}
		return [];
	}

	/**
	 * @param string $argumentName
	 * @param CompletionContext $context
	 * @return string[]
	 */
	public function completeArgumentValues($argumentName, CompletionContext $context): array {
		return [];
	}
}
