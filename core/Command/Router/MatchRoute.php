<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Router;

use OC\Core\Command\Base;
use OC\Route\Router;
use OCP\App\IAppManager;
use OCP\Server;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

class MatchRoute extends Base {

	public function __construct(
		private Router $router,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('router:match')
			->setDescription('Match a URL to the target route')
			->addArgument(
				'path',
				InputArgument::REQUIRED,
				'Path of the request',
			)
			->addOption(
				'method',
				null,
				InputOption::VALUE_REQUIRED,
				'HTTP method',
				'GET',
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$context = new RequestContext(method: strtoupper($input->getOption('method')));
		$this->router->setContext($context);

		$path = $input->getArgument('path');
		if (str_starts_with($path, '/index.php/')) {
			$path = substr($path, 10);
		}
		if (str_starts_with($path, '/ocs/v1.php/') || str_starts_with($path, '/ocs/v2.php/')) {
			$path = '/ocsapp' . substr($path, strlen('/ocs/v2.php'));
		}

		try {
			$route = $this->router->findMatchingRoute($path);
		} catch (MethodNotAllowedException) {
			$output->writeln('<error>Method not allowed on this path</error>');
			return self::FAILURE;
		} catch (ResourceNotFoundException) {
			$output->writeln('<error>Path not matched</error>');
			if (preg_match('/\/apps\/([^\/]+)\//', $path, $matches)) {
				$appManager = Server::get(IAppManager::class);
				if (!$appManager->isEnabledForAnyone($matches[1])) {
					$output->writeln('');
					$output->writeln('<comment>App ' . $matches[1] . ' is not enabled</comment>');
				}
			}
			return self::FAILURE;
		}

		$row = [
			'route' => $route['_route'],
			'appid' => $route['caller'][0] ?? null,
			'controller' => $route['caller'][1] ?? null,
			'method' => $route['caller'][2] ?? null,
		];

		if ($output->isVerbose()) {
			$route = $this->router->getRouteCollection()->get($row['route']);
			$row['path'] = $route->getPath();
			if (str_starts_with($row['path'], '/ocsapp/')) {
				$row['path'] = '/ocs/v2.php/' . substr($row['path'], strlen('/ocsapp/'));
			}
			$row['requirements'] = json_encode($route->getRequirements());
		}

		$this->writeTableInOutputFormat($input, $output, [$row]);
		return self::SUCCESS;
	}
}
