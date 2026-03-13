<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Command;

use OCP\App\IAppManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StaticHunt extends Command {
	private const array SKIP_REGEX = [
		'@/templates/.+.php$@',
		'@/ajax/.+.php$@',
		'@/register_command.php$@',
	];

	public function __construct(
		private IAppManager $appManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('testing:static-hunt')
			->setDescription('Hunt for static properties in classes');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int {
		$folders = [
			'' => __DIR__ . '/../../../../lib/private/legacy',
			'\\OC' => __DIR__ . '/../../../../lib/private',
			'\\OC\\Core' => __DIR__ . '/../../../../core',
		];
		$apps = $this->appManager->getAllAppsInAppsFolders();
		foreach ($apps as $app) {
			$info = $this->appManager->getAppInfo($app);
			if (!isset($info['namespace'])) {
				continue;
			}
			$folders['\\OCA\\' . $info['namespace']] = $this->appManager->getAppPath($app) . '/lib';
		}
		$stats = [
			'classes' => 0,
			'properties' => 0,
		];
		foreach ($folders as $namespace => $folder) {
			$this->scanFolder($folder, $namespace, $output, $stats);
		}

		$output->writeln('<info>Found ' . $stats['properties'] . ' static properties spread among ' . $stats['classes'] . ' classes</info>');

		return 0;
	}

	private function scanFolder(string $folder, string $namespace, OutputInterface $output, array &$stats): void {
		$folder = realpath($folder);
		$output->writeln('Folder ' . $folder, OutputInterface::VERBOSITY_VERBOSE);
		foreach ($this->recursiveGlob($folder) as $filename) {
			try {
				$filename = realpath($filename);
				if (($namespace === '\\OC') && str_contains($filename, 'lib/private/legacy')) {
					// Skip legacy in OC as it’s scanned with an empty namespace separately
					continue;
				}
				foreach (self::SKIP_REGEX as $skipRegex) {
					if (preg_match($skipRegex, $filename)) {
						continue 2;
					}
				}
				$classname = $namespace . substr(str_replace('/', '\\', substr($filename, strlen($folder))), 0, -4);
				$output->writeln('Class ' . $classname, OutputInterface::VERBOSITY_VERBOSE);
				if (!class_exists($classname)) {
					continue;
				}
				$rClass = new \ReflectionClass($classname);
				$staticProperties = $rClass->getStaticProperties();
				if (empty($staticProperties)) {
					continue;
				}
				$stats['classes']++;
				$output->writeln('<info># ' . str_replace(\OC::$SERVERROOT, '', $filename) . " $classname</info>");
				foreach ($staticProperties as $property => $value) {
					$propertyObject = $rClass->getProperty($property);
					$stats['properties']++;
					$output->write("$propertyObject");
				}
				$output->writeln('');
			} catch (\Throwable $t) {
				$output->writeln("$t");
			}
		}
	}

	private function recursiveGlob(string $path, int $depth = 1): \Generator {
		$pattern = $path . str_repeat('/*', $depth);
		yield from glob($pattern . '.php');
		if (!empty(glob($pattern, GLOB_ONLYDIR))) {
			yield from $this->recursiveGlob($path, $depth + 1);
		}
	}
}
