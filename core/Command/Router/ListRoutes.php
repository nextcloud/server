<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Router;

use OC\Core\Command\Base;
use OC\Route\Route;
use OC\Route\Router;
use OCP\App\IAppManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutes extends Base {

	private InputInterface $input;

	public function __construct(
		private IAppManager $appManager,
		private Router $router,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('router:list')
			->setDescription('List registered routes')
			->addArgument('appId', InputArgument::OPTIONAL, 'Limit routes to specific app', '')
			->addOption('grouped', 'g', InputOption::VALUE_NONE, 'Group routes by app');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->input = $input;

		$app = (string)$input->getArgument('appId');
		$apps = $app === '' ? $this->appManager->getEnabledApps() : [$app];

		$this->router->loadRoutes();
		$allRoutes = [];
		foreach ($apps as $appId) {
			$routes = $this->router->getCollection($appId)->all();
			$routes = array_merge($routes, $this->router->getCollection("$appId.ocs")->all());
			$allRoutes = array_merge($allRoutes, $routes);

			if (!empty($routes) && $input->getOption('grouped')) {
				$output->writeln("\nRoutes of $appId:");
				$rows = $this->formatRoutes($routes);
				$this->writeTableInOutputFormat($input, $output, $rows);
			}
		}
		if (!$input->getOption('grouped')) {
			$rows = $this->formatRoutes($allRoutes);
			$this->writeTableInOutputFormat($input, $output, $rows);
		}
		return 0;
	}

	/**
	 * @param Route[] $routes
	 */
	private function formatRoutes(array $routes): array {
		$rows = [];
		foreach ($routes as $route) {
			[$realApp, $controller, $function] = $route->getDefault('caller');
			//print_r($route->__serialize());
			$rows[] = [
				'app' => $realApp,
				'controller' => $controller,
				'function' => $function,
				'method' => $route->getMethods()[0],
				'path' => str_replace('/ocsapp', '/ocs/v2.php', $route->getPath()),
				'defaults' => $this->formatDefaults($route->getDefaults()),
				'requirements' => $this->formatArray($route->getRequirements()),
			];
		}
		return $rows;
	}

	private function formatDefaults(array $defaults): array|string {
		$defaults = array_filter($defaults, fn (string $name) => !in_array($name, ['action', 'caller']), ARRAY_FILTER_USE_KEY);
		return $this->formatArray($defaults);
	}

	private function formatArray(array $value): array|string {
		if (str_starts_with($this->input->getOption('output'), self::OUTPUT_FORMAT_JSON)) {
			return $value;
		}
		return empty($value) ? '' : json_encode($value);
	}
}
