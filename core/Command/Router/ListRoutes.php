<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Router;

use OC\Core\Command\Base;
use OC\Route\Router;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutes extends Base {

	public function __construct(
		protected IAppManager $appManager,
		protected Router $router,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('router:list')
			->setDescription('Find the target of a route or all routes of an app')
			->addArgument(
				'app',
				InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
				'Only list routes of these apps',
			)
			->addOption(
				'ocs',
				null,
				InputOption::VALUE_NONE,
				'Only list OCS routes',
			)
			->addOption(
				'index',
				null,
				InputOption::VALUE_NONE,
				'Only list index.php routes',
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$apps = $input->getArgument('app');
		if (empty($apps)) {
			$this->router->loadRoutes();
		} else {
			foreach ($apps as $app) {
				if ($app === 'core') {
					$this->router->loadRoutes($app, false);
					continue;
				}

				try {
					$this->appManager->getAppPath($app);
				} catch (AppPathNotFoundException $e) {
					$output->writeln('<comment>App ' . $app . ' not found</comment>');
					return self::FAILURE;
				}

				if (!$this->appManager->isEnabledForAnyone($app)) {
					$output->writeln('<comment>App ' . $app . ' is not enabled</comment>');
					return self::FAILURE;
				}

				$this->router->loadRoutes($app, true);
			}
		}

		$ocsOnly = $input->getOption('ocs');
		$indexOnly = $input->getOption('index');

		$rows = [];
		$collection = $this->router->getRouteCollection();
		foreach ($collection->all() as $routeName => $route) {
			if (str_starts_with($routeName, 'ocs.')) {
				if ($indexOnly) {
					continue;
				}
				$routeName = substr($routeName, 4);
			} elseif ($ocsOnly) {
				continue;
			}

			$path = $route->getPath();
			if (str_starts_with($path, '/ocsapp/')) {
				$path = '/ocs/v2.php/' . substr($path, strlen('/ocsapp/'));
			}
			$row = [
				'route' => $routeName,
				'request' => implode(', ', $route->getMethods()),
				'path' => $path,
			];

			if ($output->isVerbose()) {
				$row['requirements'] = json_encode($route->getRequirements());
			}

			$rows[] = $row;
		}

		usort($rows, static function (array $a, array $b): int {
			$aRoute = $a['route'];
			if (str_starts_with($aRoute, 'ocs.')) {
				$aRoute = substr($aRoute, 4);
			}
			$bRoute = $b['route'];
			if (str_starts_with($bRoute, 'ocs.')) {
				$bRoute = substr($bRoute, 4);
			}
			return $aRoute <=> $bRoute;
		});

		$this->writeTableInOutputFormat($input, $output, $rows);
		return self::SUCCESS;
	}
}
