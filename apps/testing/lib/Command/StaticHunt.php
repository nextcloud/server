<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing\Command;

use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\IConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StaticHunt extends Command {
	public function __construct(
		private IConfig $config,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('testing:static-hunt')
			->setDescription('Hunt for static properties in classes');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int {
		//TODO run on all apps namespaces
		$folders = [
			'\\OC' => __DIR__ . '/../../../../lib/private',
			'' => __DIR__ . '/../../../../lib/private/legacy',
		];
		foreach ($folders as $namespace => $folder) {
			$this->scanFolder($folder, $namespace, $output);
		}

		return 0;
	}

	private function scanFolder(string $folder, string $namespace, OutputInterface $output): void {
		$folder = realpath($folder);
		foreach (glob($folder.'/**.php') as $filename) {
			try {
				$filename = realpath($filename);
				$classname = $namespace.substr(str_replace('/', '\\', substr($filename, strlen($folder))), 0, -4);
				if (!class_exists($classname)) {
					continue;
				}
				$rClass = new \ReflectionClass($classname);
				$staticProperties = $rClass->getStaticProperties();
				if (empty($staticProperties)) {
					continue;
				}
				$output->writeln('<info># ' . str_replace(\OC::$SERVERROOT, '', $filename) . " $classname</info>");
				foreach ($staticProperties as $property => $value) {
					$propertyObject = $rClass->getProperty($property);
					$output->write("$propertyObject");
				}
				$output->writeln("");
			} catch (\Throwable $t) {
				$output->writeln("$t");
			}
		}
	}
}
