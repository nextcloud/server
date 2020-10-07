<?php

declare(strict_types=1);
/**
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Command\Security;

use OC\AppFramework\App;
use OC\Core\Command\Base;
use OC\Route\Route;
use OCP\Route\IRouter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutes extends Base {

	/** @var IRouter */
	protected $router;

	public function __construct(IRouter $router) {
		$this->router = $router;
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('security:routes')
			->setDescription('List used routes.')
			->addOption('with-details', 'd', InputOption::VALUE_NONE);
		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$outputType = $input->getOption('output');

		\OC_App::loadApps();
		$this->router->loadRoutes();

		$rows = [];

		if ($input->getOption('with-details')) {
			$headers = [
				'Path',
				'Methods',
				'Controller',
				'Annotations',
			];
			/** @var Route[] $collections */
			$collections = [];
			foreach ($this->router->getCollections() as $c) {
				$new = $c->all();
				$collections = $collections + $new;
			}

			foreach ($collections as $name => $route) {
				$c = $this->buildController($name);
				$rows[] = \array_merge([
					'path' => $route->getPath(),
					'methods' => $route->getMethods()
				], $c);
			}
		} else {
			$headers = [
				'Path',
				'Methods'
			];
			foreach ($this->router->getCollections() as $routeCollection) {
				foreach ($routeCollection as $route) {
					$path = $route->getPath();
					if (isset($rows[$path])) {
						$rows[$path]['methods'] = \array_unique(\array_merge($rows[$path]['methods'], $route->getMethods()));
					} else {
						$rows[$path] = [
							'path' => $path,
							'methods' => $route->getMethods()
						];
					}
					\sort($rows[$path]['methods']);
				}
			}
		}
		\usort($rows, function ($a, $b) {
			return \strcmp($a['path'], $b['path']);
		});
		$rows = \array_map(function ($row) {
			$row['methods'] = \implode(',', $row['methods']);
			return $row;
		}, $rows);

		if ($outputType === self::OUTPUT_FORMAT_JSON) {
			$output->write(\json_encode($rows));
		} elseif ($outputType === self::OUTPUT_FORMAT_JSON_PRETTY) {
			$output->writeln(\json_encode($rows, JSON_PRETTY_PRINT));
		} else {
			$table = new Table($output);
			$table->setHeaders($headers);

			$table->addRows($rows);
			$table->render();
		}
		return 0;
	}

	private function buildController(string $name): array {
		$parts = \explode('.', $name);
		if (\count($parts) === 4 && $parts[0] === 'ocs') {
			\array_shift($parts);
		}
		if (\count($parts) !== 3) {
			return [
				'controllerClass' => '*** not controller based ***'
			];
		}
		$appName = $parts[0];
		$controllerName = $parts[1];
		$method = $parts[2];
		$reflection = $this->buildReflection($appName, $controllerName, $method);
		if ($reflection === null) {
			return [
				'controllerClass' => '*** controller not resolvable ***'
			];
		}
		$docs = $reflection->getDocComment();

		// extract everything prefixed by @ and first letter uppercase
		\preg_match_all('/@([A-Z]\w+)/', $docs, $matches);
		$annotations = $matches[1];

		return [
			'controllerClass' => $reflection->getDeclaringClass()->getName() . '::' . $reflection->getName(),
			'annotations' => \implode(',', $annotations),
		];
	}

	/**
	 * @param string $appName
	 * @param string $controllerName
	 * @param string $method
	 * @return null|\ReflectionMethod
	 */
	private function buildReflection(string $appName, string $controllerName, string $method): ?\ReflectionMethod {
		foreach ($this->listControllerNames($appName, $controllerName) as $controllerName) {
			foreach ($this->listMethodNames($method) as $m) {
				try {
					$reflection = new \ReflectionMethod($controllerName, $m);
					return $reflection;
				} catch (\ReflectionException $ex) {
				}
			}
		}
		return null;
	}

	/**
	 * @param string $appName
	 * @param string $controllerName
	 * @return \Generator|string[]
	 */
	private function listControllerNames(string $appName, string $controllerName) {
		foreach ([App::buildAppNamespace($appName), App::buildAppNamespace($appName, 'OC\\')] as $appNameSpace) {
			foreach (['\\Controller\\', '\\Controllers\\'] as $namespace) {
				yield $appNameSpace . $namespace . $controllerName;
				yield $appNameSpace . $namespace . \ucfirst(\strtolower($controllerName));
				yield $appNameSpace . $namespace . $controllerName . 'Controller';
				yield $appNameSpace . $namespace . \ucfirst(\strtolower($controllerName)) . 'Controller';
				$controllerName = \implode('', \array_map(function ($word) {
					return \ucfirst($word);
				}, \explode('_', $controllerName)));
				yield $appNameSpace . $namespace . $controllerName;
				yield $appNameSpace . $namespace . \ucfirst(\strtolower($controllerName));
				yield $appNameSpace . $namespace . $controllerName . 'Controller';
				yield $appNameSpace . $namespace . \ucfirst(\strtolower($controllerName)) . 'Controller';
			}
		}
	}

	/**
	 * @param string $method
	 * @return \Generator|string[]
	 */
	private function listMethodNames(string $method) {
		yield $method;
		yield \implode('', \explode('_', $method));
		foreach (['post', 'put'] as $verb) {
			if (\substr($method, -\strlen($verb)) == $verb) {
				yield \substr($method, 0, -\strlen($verb));
				yield \implode('', \explode('_', \substr($method, 0, -\strlen($verb))));
			}
		}
	}
}
